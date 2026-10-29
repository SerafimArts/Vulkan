<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer\OutputLengths;
use Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer\ParameterRoles;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Decides which scheme a command is built after.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CommandAnalyzer
{
    private ParameterRoles $roles;

    private OutputLengths $lengths;

    public function __construct(
        private Registry $registry,
        private TypeResolver $types,
    ) {
        $this->roles = new ParameterRoles($types);
        $this->lengths = new OutputLengths($registry);
    }

    /**
     * @throws UnsupportedDeclarationException in case of the command does not
     *         fit any scheme
     */
    public function analyze(CommandDefinition $command): CommandPlan
    {
        $returnsResult = $command->returnType === 'VkResult';

        $returnKind = null;

        if (!$returnsResult && $command->returnType !== 'void') {
            $returnKind = $this->types->resolve($command->returnType);

            $returned = [TypeKind::BOOLEAN, TypeKind::INTEGER, TypeKind::CALLBACK];

            if (!\in_array($returnKind, $returned, true)) {
                throw $this->createException($command, 'the return type is not supported');
            }
        }

        $parameters = $this->roles->create($command);
        $outputs = \array_values(\array_filter($parameters, $this->isOutput(...)));
        $count = $this->findCount($parameters);

        if ($outputs !== [] && $returnKind !== null) {
            throw $this->createException($command, 'the command returns a value and has outputs');
        }

        if (\count($outputs) > 1) {
            return $this->analyzeMultiple($command, $parameters, $outputs, $returnsResult);
        }

        $output = $outputs[0] ?? null;

        if ($output === null) {
            return new CommandPlan($command, CommandKind::EXECUTE, $parameters, null, $returnsResult, null, $returnKind);
        }

        $handles = $this->findHandles($output);

        if ($handles !== null) {
            return new CommandPlan(
                $command,
                CommandKind::CREATE_HANDLES,
                $parameters,
                $output,
                $returnsResult,
                handles: $handles,
            );
        }

        $outputLength = null;

        $isArray = $output->role === ParameterRole::OUTPUT_ARRAY || $output->role === ParameterRole::OUTPUT_BINARY;

        if ($isArray && $count === null) {
            $outputLength = $this->lengths->find($parameters, $output);

            if ($outputLength === null) {
                throw $this->createException($command, 'the length of the output array is unknown');
            }
        }

        $kind = match ($output->role) {
            ParameterRole::OUTPUT_HANDLE => CommandKind::CREATE,
            ParameterRole::OUTPUT_STRUCTURE => CommandKind::QUERY,
            ParameterRole::OUTPUT_SCALAR => CommandKind::QUERY_SCALAR,
            ParameterRole::OUTPUT_POINTER => CommandKind::QUERY_POINTER,
            default => CommandKind::ENUMERATE,
        };

        if ($outputLength !== null) {
            $kind = match (true) {
                $output->role === ParameterRole::OUTPUT_BINARY => CommandKind::QUERY_BINARY,
                $output->kind === TypeKind::HANDLE => CommandKind::CREATE_ARRAY,
                default => CommandKind::QUERY_ARRAY,
            };
        }

        $isReleasable = $kind === CommandKind::CREATE
            && !$command->isGetter()
            && $this->registry->hasReleaseCommand($output->field->type);

        if ($isReleasable && $this->registry->findReleaseCommand($output->field->type) === null) {
            throw $this->createException($command, 'the command that releases the handle is unknown');
        }

        return new CommandPlan(
            $command,
            $kind,
            $parameters,
            $output,
            $returnsResult,
            $outputLength,
            requested: $this->findRequested($kind, $parameters, $output, $outputLength),
        );
    }

    /**
     * Returns the parameter with the number of the structures and the member
     * that the user gives, when the structures are filled in part by the user.
     *
     * @param list<ParameterPlan> $parameters
     *
     * @return array{non-empty-string, non-empty-string}|null
     */
    private function findRequested(
        CommandKind $kind,
        array $parameters,
        ParameterPlan $output,
        ?string $outputLength,
    ): ?array {
        $member = MixedStructures::findInput($output->field->type);

        if ($kind !== CommandKind::QUERY_ARRAY || $member === null || $outputLength === null) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if ($parameter->role === ParameterRole::SCALAR && '$' . $parameter->name === $outputLength) {
                /** @var non-empty-string $name */
                $name = $parameter->name;

                return [$name, $member];
            }
        }

        return null;
    }

    /**
     * A command that fills several outputs: the arrays of unknown length are
     * filled in two calls and share the length, the others are filled by the
     * user's length.
     *
     * @param list<ParameterPlan> $parameters
     * @param list<ParameterPlan> $outputs
     *
     * @throws UnsupportedDeclarationException
     */
    private function analyzeMultiple(
        CommandDefinition $command,
        array $parameters,
        array $outputs,
        bool $returnsResult,
    ): CommandPlan {
        $roles = [
            ParameterRole::OUTPUT_SCALAR,
            ParameterRole::OUTPUT_STRUCTURE,
            ParameterRole::OUTPUT_ARRAY,
            ParameterRole::OUTPUT_BINARY,
        ];
        $plan = new CommandPlan($command, CommandKind::QUERY_MULTIPLE, $parameters, $outputs[0], $returnsResult);
        $lengths = [];
        $enumerated = [];

        foreach ($outputs as $output) {
            if (!\in_array($output->role, $roles, true)) {
                throw $this->createException($command, 'the output is not supported together with others');
            }

            if ($plan->isEnumerated($output)) {
                $enumerated[] = $output;

                continue;
            }

            if ($output->role === ParameterRole::OUTPUT_ARRAY || $output->role === ParameterRole::OUTPUT_BINARY) {
                $length = $this->lengths->find($parameters, $output);

                if ($length === null) {
                    throw $this->createException($command, 'the length of the output array is unknown');
                }

                $lengths[$output->name] = $length;
            }
        }

        if (\count($enumerated) > 1 && !$this->isParallel($enumerated)) {
            throw $this->createException($command, 'the command fills several arrays of unknown length');
        }

        return new CommandPlan(
            $command,
            CommandKind::QUERY_MULTIPLE,
            $parameters,
            $outputs[0],
            $returnsResult,
            null,
            null,
            $outputs,
            $lengths,
        );
    }

    /**
     * Whether the arrays of structures share the length: each of them is
     * filled with the same number of elements.
     *
     * @param list<ParameterPlan> $arrays
     */
    private function isParallel(array $arrays): bool
    {
        foreach ($arrays as $array) {
            if ($array->role !== ParameterRole::OUTPUT_ARRAY || $array->kind !== TypeKind::STRUCTURE) {
                return false;
            }

            if ($array->field->length !== $arrays[0]->field->length) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the member that holds the created handles when the structure
     * the command fills consists of nothing but their number and the handles.
     */
    private function findHandles(ParameterPlan $output): ?FieldDefinition
    {
        if ($output->role !== ParameterRole::OUTPUT_STRUCTURE) {
            return null;
        }

        $struct = $this->registry->findStruct($output->field->type);

        if ($struct === null) {
            return null;
        }

        $handles = null;
        $others = [];

        foreach ($struct->members as $member) {
            $isHandles = $member->pointerDepth === 1
                && !$member->isConst
                && $member->length !== null
                && $this->types->resolve($member->type) === TypeKind::HANDLE;

            if ($isHandles) {
                $handles = $member;

                continue;
            }

            if (!\in_array($member->name, ['sType', 'pNext'], true)) {
                $others[] = $member->name;
            }
        }

        if ($handles === null || $others !== [$handles->length]) {
            return null;
        }

        return $handles;
    }

    private function isOutput(ParameterPlan $parameter): bool
    {
        return \in_array($parameter->role, [
            ParameterRole::OUTPUT_HANDLE,
            ParameterRole::OUTPUT_STRUCTURE,
            ParameterRole::OUTPUT_ARRAY,
            ParameterRole::OUTPUT_BINARY,
            ParameterRole::OUTPUT_SCALAR,
            ParameterRole::OUTPUT_POINTER,
        ], true);
    }

    /**
     * @param list<ParameterPlan> $parameters
     */
    private function findCount(array $parameters): ?ParameterPlan
    {
        foreach ($parameters as $parameter) {
            if ($parameter->role === ParameterRole::OUTPUT_COUNT) {
                return $parameter;
            }
        }

        return null;
    }

    private function createException(CommandDefinition $command, string $reason): UnsupportedDeclarationException
    {
        return UnsupportedDeclarationException::becauseDeclarationIsNotSupported($command->name, $reason);
    }
}
