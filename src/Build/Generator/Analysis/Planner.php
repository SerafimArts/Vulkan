<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Plans the whole API of the registry. A command that cannot be represented
 * is left out together with the reason.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Planner
{
    private CommandAnalyzer $commands;

    private StructAnalyzer $structs;

    public function __construct(
        private Registry $registry,
        TypeResolver $types,
    ) {
        $this->commands = new CommandAnalyzer($registry, $types);
        $this->structs = new StructAnalyzer($registry, $types);
    }

    /**
     * @throws InvalidRegistryException in case of a definition is missing
     */
    public function plan(): GenerationPlan
    {
        $commands = [];
        $structs = [];
        $skipped = [];

        foreach ($this->registry->getCommands() as $definition) {
            try {
                $command = $this->commands->analyze($definition);
                $collected = $structs;

                foreach ($command->parameters as $parameter) {
                    $collected = $this->collectStructs($parameter, $collected);
                }
            } catch (UnsupportedDeclarationException $e) {
                /** @var non-empty-string $reason */
                $reason = $e->getMessage();
                $skipped[$definition->name] = $reason;

                continue;
            }

            $structs = $collected;
            $commands[] = $command;
        }

        $structs = $this->collectExtenders($structs);
        $structs = $this->collectExtenders($this->collectOutputExtenders($structs));

        [$structs, $extensions] = $this->resolveChains($structs);

        return new GenerationPlan(
            commands: $commands,
            structs: \array_values($structs),
            handles: $this->registry->getHandles(),
            enums: $this->registry->getEnums(),
            skipped: $skipped,
            extensions: $extensions,
        );
    }

    /**
     * Adds the structures that can be chained to the structures of the user,
     * the ones that cannot be represented are left out.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     */
    private function collectExtenders(array $structs): array
    {
        $queue = \array_keys(\array_filter($structs, $this->hasNext(...)));

        while ($queue !== []) {
            $name = \array_shift($queue);

            foreach ($this->registry->findExtenders($name) as $extender) {
                if (isset($structs[$extender->name])) {
                    continue;
                }

                try {
                    $collected = $this->collectStruct($extender->name, StructRole::INPUT, $structs);
                } catch (UnsupportedDeclarationException) {
                    continue;
                }

                foreach (\array_diff_key($collected, $structs) as $added => $plan) {
                    if ($this->hasNext($plan)) {
                        $queue[] = $added;
                    }
                }

                $structs = $collected;
            }
        }

        return $structs;
    }

    /**
     * Adds the structures that a command can fill in the chain of a returned
     * structure. The ones that cannot be read are left out.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     */
    private function collectOutputExtenders(array $structs): array
    {
        $queue = \array_keys(\array_filter(
            $structs,
            static fn(StructPlan $plan): bool => $plan->role !== StructRole::INPUT,
        ));
        $visited = [];

        while ($queue !== []) {
            $name = \array_shift($queue);

            if (isset($visited[$name])) {
                continue;
            }

            $visited[$name] = true;

            foreach ($this->registry->findExtenders($name) as $extender) {
                try {
                    $collected = $this->collectStruct($extender->name, StructRole::OUTPUT, $structs);
                } catch (UnsupportedDeclarationException) {
                    continue;
                }

                foreach (\array_keys(\array_diff_key($collected, $structs)) as $added) {
                    $queue[] = $added;
                }

                $structs = $collected;
            }
        }

        return $structs;
    }

    /**
     * Decides which structures each `pNext` accepts. A structure that nothing
     * can be chained to has no `pNext`.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array{array<non-empty-string, StructPlan>, list<non-empty-string>} the structures
     *         and the names of the ones that others are chained to
     */
    private function resolveChains(array $structs): array
    {
        $extended = [];

        foreach ($structs as $plan) {
            foreach ($plan->struct->extends as $root) {
                if (isset($structs[$root])) {
                    $extended[$root] = true;
                }
            }
        }

        foreach ($structs as $name => $plan) {
            $implements = \array_values(\array_filter(
                $plan->struct->extends,
                static fn(string $root): bool => isset($extended[$root]),
            ));
            $chains = $implements;

            if (isset($extended[$name])) {
                \array_unshift($chains, $name);
            }

            $isRoot = isset($extended[$name]);
            $properties = \array_values(\array_filter(
                $plan->properties,
                static fn(PropertyPlan $property): bool => match ($property->kind) {
                    PropertyKind::NEXT => $chains !== [],
                    PropertyKind::CHAIN => $isRoot,
                    default => true,
                },
            ));

            $structs[$name] = new StructPlan(
                $plan->struct,
                $plan->role,
                $properties,
                $plan->structureType,
                $chains,
                $implements,
            );
        }

        return [$structs, \array_keys($extended)];
    }

    private function hasNext(StructPlan $plan): bool
    {
        foreach ($plan->properties as $property) {
            if ($property->kind === PropertyKind::NEXT) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds the structures the parameter needs, with the ones they need.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     * @throws UnsupportedDeclarationException
     */
    private function collectStructs(ParameterPlan $parameter, array $structs): array
    {
        $role = match ($parameter->role) {
            ParameterRole::INPUT_STRUCTURE, ParameterRole::INPUT_ARRAY => StructRole::INPUT,
            ParameterRole::OUTPUT_STRUCTURE, ParameterRole::OUTPUT_ARRAY => StructRole::OUTPUT,
            default => null,
        };

        if ($role === null) {
            return $structs;
        }

        $isArray = \in_array($parameter->role, [ParameterRole::OUTPUT_ARRAY, ParameterRole::INPUT_ARRAY], true);

        if ($isArray && $parameter->kind !== TypeKind::STRUCTURE) {
            return $structs;
        }

        return $this->collectStruct($parameter->field->type, $role, $structs);
    }

    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     * @throws UnsupportedDeclarationException
     */
    private function collectStruct(string $name, StructRole $role, array $structs): array
    {
        $existing = $structs[$name] ?? null;

        if ($existing !== null) {
            if ($existing->role === $role || $existing->role === StructRole::DUAL) {
                return $structs;
            }

            $role = StructRole::DUAL;
        }

        $plan = $this->structs->analyze($this->registry->getStruct($name), $role);

        $structs[$name] = $plan;

        foreach ($plan->properties as $property) {
            $nested = [
                PropertyKind::STRUCTURE_POINTER,
                PropertyKind::STRUCTURE_VALUE,
                PropertyKind::STRUCTURE_ARRAY,
                PropertyKind::STRUCTURE_LIST,
            ];

            if (\in_array($property->kind, $nested, true)) {
                $structs = $this->collectStruct($property->field->type, $role, $structs);
            }
        }

        return $structs;
    }
}
