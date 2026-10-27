<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;

/**
 * Definitions of the Vulkan API read from `vk.xml`.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Registry
{
    public function __construct(
        /**
         * @var array<non-empty-string, StructDefinition>
         */
        private array $structs,
        /**
         * @var array<non-empty-string, CommandDefinition>
         */
        private array $commands,
        /**
         * @var array<non-empty-string, HandleDefinition>
         */
        private array $handles,
        /**
         * @var array<non-empty-string, EnumDefinition>
         */
        private array $enums,
        /**
         * The C types of the flag types and of their bits by the names
         *
         * @var array<non-empty-string, non-empty-string>
         */
        private array $bitmasks,
        /**
         * C types by the names of the typedefs
         *
         * @var array<non-empty-string, non-empty-string>
         */
        private array $baseTypes,
        /**
         * @var array<non-empty-string, int>
         */
        private array $constants,
        /**
         * Names of the types of the bits by the names of the flag types
         *
         * @var array<non-empty-string, non-empty-string>
         */
        private array $flagBits = [],
        /**
         * The version of Vulkan or the extensions a command or a type appeared
         * in
         *
         * @var array<non-empty-string, non-empty-string>
         */
        private array $since = [],
        /**
         * @var array<non-empty-string, Deprecation>
         */
        private array $deprecations = [],
        /**
         * Names of the commands by the names of their aliases
         *
         * @var array<non-empty-string, non-empty-string>
         */
        private array $commandAliases = [],
        /**
         * The `#define` constants
         *
         * @var list<Constant>
         */
        private array $defines = [],
    ) {}

    /**
     * Returns the version of Vulkan or the extensions a command or a type
     * appeared in.
     */
    public function findSince(string $name): ?string
    {
        return $this->since[$name] ?? null;
    }

    public function findDeprecation(string $name): ?Deprecation
    {
        return $this->deprecations[$name] ?? null;
    }

    /**
     * @return list<CommandDefinition>
     */
    public function getCommands(): array
    {
        return \array_values($this->commands);
    }

    /**
     * @return list<HandleDefinition>
     */
    public function getHandles(): array
    {
        return \array_values($this->handles);
    }

    /**
     * @return list<EnumDefinition>
     */
    public function getEnums(): array
    {
        return \array_values($this->enums);
    }

    /**
     * @return list<Constant>
     */
    public function getDefines(): array
    {
        return $this->defines;
    }

    /**
     * Whether the structure starts with `sType`, which the user fills before a
     * command writes the structure.
     */
    public function isTypedStructure(string $name): bool
    {
        foreach ($this->structs[$name]->members ?? [] as $member) {
            if ($member->name === 'sType') {
                return $member->value !== null;
            }
        }

        return false;
    }

    /**
     * Returns the names of the aliases of a command.
     *
     * @return list<non-empty-string>
     */
    public function findAliases(string $command): array
    {
        return \array_keys($this->commandAliases, $command, true);
    }

    /**
     * Returns the structures that can be chained to the structure with
     * `pNext`.
     *
     * @return list<StructDefinition>
     */
    public function findExtenders(string $name): array
    {
        $extenders = [];

        foreach ($this->structs as $struct) {
            if (\in_array($name, $struct->extends, true) && $this->hasStructureType($struct)) {
                $extenders[] = $struct;
            }
        }

        return $extenders;
    }

    /**
     * Tells whether the `sType` value of a structure is defined: the
     * structures of the disabled extensions have none.
     */
    private function hasStructureType(StructDefinition $struct): bool
    {
        foreach ($struct->members as $member) {
            if ($member->value !== null) {
                return isset($this->enums['VkStructureType']->cases[$member->value]);
            }
        }

        return true;
    }

    public function findStruct(string $name): ?StructDefinition
    {
        return $this->structs[$name] ?? null;
    }

    /**
     * @throws InvalidRegistryException in case of there is no such structure
     */
    public function getStruct(string $name): StructDefinition
    {
        return $this->findStruct($name)
            ?? throw InvalidRegistryException::becauseDefinitionNotFound('structure', $name);
    }

    public function findHandle(string $name): ?HandleDefinition
    {
        return $this->handles[$name] ?? null;
    }

    public function findEnum(string $name): ?EnumDefinition
    {
        return $this->enums[$name] ?? null;
    }

    /**
     * @throws InvalidRegistryException in case of there is no such enum
     */
    public function getEnum(string $name): EnumDefinition
    {
        return $this->findEnum($name)
            ?? throw InvalidRegistryException::becauseDefinitionNotFound('enum', $name);
    }

    /**
     * Returns the command that releases the handle: the one whose last
     * argument besides the allocator is the handle itself.
     */
    public function findReleaseCommand(string $handle): ?CommandDefinition
    {
        foreach ($this->commands as $command) {
            if (!$command->isReleasing()) {
                continue;
            }

            $target = null;

            foreach ($command->parameters as $parameter) {
                if ($parameter->type !== 'VkAllocationCallbacks') {
                    $target = $parameter;
                }
            }

            if ($target?->type === $handle && $target->pointerDepth === 0 && $target->length === null) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Returns the name of the case with the zero value, the default of a
     * member in C.
     */
    public function findZeroCase(string $enum): ?string
    {
        $name = \array_search(0, $this->findEnum($enum)->cases ?? [], true);

        if (\is_string($name)) {
            return $name;
        }

        return null;
    }

    /**
     * Returns the name of the type of the bits, of a flag type or of the bits
     * themselves.
     *
     * @return non-empty-string|null
     */
    public function findFlagBits(string $name): ?string
    {
        $enum = $this->enums[$this->flagBits[$name] ?? $name] ?? null;

        if ($enum?->isFlags === true) {
            return $enum->name;
        }

        return null;
    }

    /**
     * Returns how many values a type of the bits has.
     *
     * @return int<0, max>
     */
    public function countFlagBits(string $bits): int
    {
        return \count($this->enums[$bits]->cases ?? []);
    }

    /**
     * Returns the C type (`VkFlags` or `VkFlags64`) of a flag type or of its bits.
     *
     * @return non-empty-string|null
     */
    public function findBitmaskType(string $name): ?string
    {
        return $this->bitmasks[$name] ?? null;
    }

    /**
     * Returns the C type the typedef is declared as.
     */
    public function findBaseType(string $name): ?string
    {
        return $this->baseTypes[$name] ?? null;
    }

    public function findConstant(string $name): ?int
    {
        return $this->constants[$name] ?? null;
    }
}
