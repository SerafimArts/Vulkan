<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Quirks;
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
            if (!$this->registry->isDeclared('PFN_' . $definition->name)) {
                $skipped[$definition->name] = 'the command is not declared in the headers';

                continue;
            }

            try {
                $command = $this->commands->analyze($definition);
                $collected = $structs;

                foreach ($command->parameters as $parameter) {
                    if ($command->kind === CommandKind::CREATE_HANDLES && $parameter === $command->output) {
                        continue;
                    }

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

        $rejected = [];

        $structs = $this->collectExtenders($structs, $rejected);
        $structs = $this->collectExtenders($this->collectOutputExtenders($structs, $rejected), $rejected);
        $structs = $this->collectRemaining($structs, $rejected);

        [$structs, $extensions] = $this->resolveChains($structs);
        $structs = $this->resolveVersions($structs);

        return new GenerationPlan(
            commands: $commands,
            structs: \array_values($structs),
            handles: $this->registry->getHandles(),
            enums: $this->registry->getEnums(),
            skipped: \array_filter(
                $skipped,
                fn(string $reason, string $name): bool => !$this->registry->isExcluded($name),
                \ARRAY_FILTER_USE_BOTH,
            ),
            extensions: $extensions,
            skippedStructs: \array_filter(
                \array_diff_key($rejected, $structs),
                fn(string $reason, string $name): bool => !$this->registry->isExcluded($name),
                \ARRAY_FILTER_USE_BOTH,
            ),
        );
    }

    /**
     * Adds the structures that can be chained to the structures of the user,
     * the ones that cannot be represented are left out.
     *
     * @param array<non-empty-string, StructPlan> $structs
     * @param array<non-empty-string, non-empty-string> $rejected the reasons
     *        by the names of the structures that cannot be represented
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     */
    private function collectExtenders(array $structs, array &$rejected): array
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
                } catch (UnsupportedDeclarationException $e) {
                    $rejected[$extender->name] = $this->createReason($e);

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
     * Adds the structures that no command refers to: the user writes them to
     * the memory of the buffers (the arguments of the indirect commands), or
     * Vulkan fills them in a chain. The ones that cannot be represented are
     * left out.
     *
     * @param array<non-empty-string, StructPlan> $structs
     * @param array<non-empty-string, non-empty-string> $rejected
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     */
    private function collectRemaining(array $structs, array &$rejected): array
    {
        foreach ($this->registry->getStructs() as $struct) {
            if (isset($structs[$struct->name]) || !$this->registry->hasStructureType($struct)) {
                continue;
            }

            $role = StructRole::INPUT;

            if ($struct->isReturnedOnly) {
                $role = StructRole::OUTPUT;
            }

            try {
                $structs = $this->collectStruct($struct->name, $role, $structs);
            } catch (UnsupportedDeclarationException $e) {
                $rejected[$struct->name] = $this->createReason($e);

                continue;
            }
        }

        return $structs;
    }

    /**
     * Adds the structures that a command can fill in the chain of a returned
     * structure. The ones that cannot be read are left out.
     *
     * @param array<non-empty-string, StructPlan> $structs
     * @param array<non-empty-string, non-empty-string> $rejected
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException
     */
    private function collectOutputExtenders(array $structs, array &$rejected): array
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
                } catch (UnsupportedDeclarationException $e) {
                    $rejected[$extender->name] = $this->createReason($e);

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
            $isRoot = isset($extended[$name]);

            if (isset($extended[$name])) {
                \array_unshift($chains, $name);
            }

            $properties = \array_values(\array_filter(
                $plan->properties,
                static fn(PropertyPlan $property): bool => match ($property->kind) {
                    PropertyKind::NEXT => $isRoot,
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
                counts: $plan->counts,
            );
        }

        $contextual = $this->findContextual($structs);

        foreach ($contextual as $name => $_) {
            $plan = $structs[$name];

            $structs[$name] = new StructPlan(
                $plan->struct,
                $plan->role,
                $plan->properties,
                $plan->structureType,
                $plan->chains,
                $plan->implements,
                true,
                $plan->counts,
            );
        }

        return [$structs, \array_keys($extended)];
    }

    /**
     * Makes the structure of every version of Vulkan extend the one of the
     * previous version: it keeps only the members the version has added.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, StructPlan>
     * @throws InvalidRegistryException in case of the structures of the
     *         versions do not extend each other
     */
    private function resolveVersions(array $structs): array
    {
        $kinds = [];

        foreach (\array_keys($structs) as $name) {
            $version = Quirks::findVersion($name);

            if ($version !== null) {
                $kinds[$version[0]][$version[1]] = $name;
            }
        }

        foreach ($kinds as $versions) {
            \ksort($versions);

            $parent = null;
            $number = 0;
            $members = [];

            foreach ($versions as $current => $name) {
                $plan = $structs[$name];
                $own = \array_values(\array_filter(
                    $plan->properties,
                    static fn(PropertyPlan $property): bool => $property->kind !== PropertyKind::NEXT
                        && $property->kind !== PropertyKind::CHAIN,
                ));

                if ($parent === null) {
                    $parent = $plan;
                    $number = $current;
                    $members = $this->findMemberNames($plan->properties);

                    continue;
                }

                $this->assertVersionExtends($plan, $parent, $current === $number + 1, $own, $members);

                $structs[$parent->struct->name] = $this->createVersioned($parent, $parent->properties, $parent->ancestors, $parent->inherited, true);

                $parent = $this->createVersioned(
                    $plan,
                    $own,
                    [$parent->struct->name, ...$parent->ancestors],
                    [...$parent->inherited, ...$parent->properties],
                    false,
                );
                $structs[$name] = $parent;
                $number = $current;
                $members = [...$members, ...$this->findMemberNames($own)];
            }
        }

        return $structs;
    }

    /**
     * @param list<PropertyPlan> $own
     * @param list<string> $members the members of the previous versions
     *
     * @throws InvalidRegistryException
     */
    private function assertVersionExtends(
        StructPlan $plan,
        StructPlan $parent,
        bool $isNext,
        array $own,
        array $members,
    ): void {
        $name = $plan->struct->name;

        if (!$isNext) {
            throw InvalidRegistryException::becauseVersionsAreInconsistent($name, 'the previous version is missing');
        }

        if ($plan->role !== $parent->role || $plan->struct->extends !== $parent->struct->extends) {
            throw InvalidRegistryException::becauseVersionsAreInconsistent(
                $name,
                'the structure is read or extends the structures differently from the previous version',
            );
        }

        $repeated = \array_intersect($this->findMemberNames($own), $members);

        if ($repeated !== []) {
            throw InvalidRegistryException::becauseVersionsAreInconsistent(
                $name,
                'the members of the previous versions are repeated: ' . \implode(', ', $repeated),
            );
        }
    }

    /**
     * @param list<PropertyPlan> $properties
     *
     * @return list<string>
     */
    private function findMemberNames(array $properties): array
    {
        $names = [];

        foreach ($properties as $property) {
            if ($property->kind !== PropertyKind::NEXT && $property->kind !== PropertyKind::CHAIN) {
                $names[] = $property->field->name;
            }
        }

        return $names;
    }

    /**
     * @param list<PropertyPlan> $properties
     * @param list<non-empty-string> $ancestors
     * @param list<PropertyPlan> $inherited
     */
    private function createVersioned(
        StructPlan $plan,
        array $properties,
        array $ancestors,
        array $inherited,
        bool $hasDescendants,
    ): StructPlan {
        return new StructPlan(
            $plan->struct,
            $plan->role,
            $properties,
            $plan->structureType,
            $plan->chains,
            $plan->implements,
            $plan->needsContext,
            $plan->counts,
            $ancestors,
            $inherited,
            $hasDescendants,
        );
    }

    /**
     * Finds the returned structures that hold handles, directly or in the
     * structures they contain.
     *
     * @param array<non-empty-string, StructPlan> $structs
     *
     * @return array<non-empty-string, true>
     */
    private function findContextual(array $structs): array
    {
        $contextual = [];

        do {
            $changed = false;

            foreach ($structs as $name => $plan) {
                if ($plan->role !== StructRole::OUTPUT || isset($contextual[$name])) {
                    continue;
                }

                foreach ($plan->properties as $property) {
                    if ($this->holdsContext($property, $contextual)) {
                        $contextual[$name] = true;
                        $changed = true;

                        break;
                    }
                }
            }
        } while ($changed);

        return $contextual;
    }

    /**
     * @param array<non-empty-string, true> $contextual
     */
    private function holdsContext(PropertyPlan $property, array $contextual): bool
    {
        return match ($property->kind) {
            PropertyKind::HANDLE, PropertyKind::HANDLE_LIST => true,
            PropertyKind::STRUCTURE_VALUE, PropertyKind::STRUCTURE_LIST => isset($contextual[$property->field->type]),
            default => false,
        };
    }

    /**
     * @return non-empty-string
     */
    private function createReason(UnsupportedDeclarationException $e): string
    {
        /** @var non-empty-string $reason */
        $reason = $e->getMessage();

        return $reason;
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
        if (!$this->registry->isDeclared($name)) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $name,
                'the structure is not declared in the headers',
            );
        }

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
            if ($property->kind->isNested()) {
                $structs = $this->collectStruct($property->field->type, $role, $structs);
            }

            $isFilledStructure = $property->kind === PropertyKind::QUERIED_ARRAY
                && $this->registry->findStruct($property->field->type) !== null;

            if ($isFilledStructure) {
                $structs = $this->collectStruct($property->field->type, StructRole::OUTPUT, $structs);
            }

            if ($property->kind === PropertyKind::FEEDBACK_STRUCTURE || $property->kind === PropertyKind::FEEDBACK_ARRAY) {
                $structs = $this->collectStruct($property->field->type, StructRole::OUTPUT, $structs);
            }
        }

        return $structs;
    }
}
