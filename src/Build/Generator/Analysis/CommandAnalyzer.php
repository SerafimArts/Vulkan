<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer\OutputLengths;
use Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer\ParameterRoles;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;
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
        TypeResolver $types,
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

        if (!$returnsResult && $command->returnType !== 'void') {
            throw $this->createException($command, 'the return type is not supported');
        }

        $parameters = $this->roles->create($command);
        $outputs = \array_values(\array_filter($parameters, $this->isOutput(...)));
        $count = $this->findCount($parameters);

        if (\count($outputs) > 1) {
            throw $this->createException($command, 'the command has several outputs');
        }

        $output = $outputs[0] ?? null;

        if ($output === null) {
            return new CommandPlan($command, CommandKind::EXECUTE, $parameters, null, $returnsResult);
        }

        $outputLength = null;

        if ($output->role === ParameterRole::OUTPUT_ARRAY && $count === null) {
            $outputLength = $this->lengths->find($parameters, $output);

            if ($outputLength === null || $output->kind !== TypeKind::HANDLE) {
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
            $kind = CommandKind::CREATE_ARRAY;
        }

        $isValue = $kind === CommandKind::QUERY_SCALAR || $kind === CommandKind::QUERY_POINTER;

        if ($isValue && \count($command->successCodes) > 1) {
            throw $this->createException($command, 'the command returns both a status and a value');
        }

        $isReleasable = $kind === CommandKind::CREATE
            && !$command->isGetter()
            && $this->registry->hasReleaseCommand($output->field->type);

        if ($isReleasable && $this->registry->findReleaseCommand($output->field->type) === null) {
            throw $this->createException($command, 'the command that releases the handle is unknown');
        }

        return new CommandPlan($command, $kind, $parameters, $output, $returnsResult, $outputLength);
    }

    private function isOutput(ParameterPlan $parameter): bool
    {
        return \in_array($parameter->role, [
            ParameterRole::OUTPUT_HANDLE,
            ParameterRole::OUTPUT_STRUCTURE,
            ParameterRole::OUTPUT_ARRAY,
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
