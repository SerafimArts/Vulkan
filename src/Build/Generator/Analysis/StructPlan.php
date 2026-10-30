<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\StructDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructPlan
{
    public function __construct(
        public StructDefinition $struct,
        public StructRole $role,
        /**
         * @var list<PropertyPlan>
         */
        public array $properties,
        /**
         * Name of the `sType` constant
         */
        public ?string $structureType,
        /**
         * Names of the structures whose chains the `pNext` of this one accepts
         *
         * @var list<non-empty-string>
         */
        public array $chains = [],
        /**
         * Names of the structures it can be chained to
         *
         * @var list<non-empty-string>
         */
        public array $implements = [],
        /**
         * Whether a returned structure holds handles, it is created in the
         * context of the commands that created them
         */
        public bool $needsContext = false,
        /**
         * The structure that holds the lengths of the arrays, the first call
         * of the command fills it
         *
         * @var non-empty-string|null
         */
        public ?string $counts = null,
        /**
         * The structures of the previous versions of Vulkan whose members
         * this one holds, the nearest first
         *
         * @var list<non-empty-string>
         */
        public array $ancestors = [],
        /**
         * The properties of the structure of the previous version, they are
         * the first arguments of the constructor
         *
         * @var list<PropertyPlan>
         */
        public array $inherited = [],
        /**
         * Whether the structure of the next version extends this one
         */
        public bool $hasDescendants = false,
        /**
         * The version of Vulkan the structure gathers the members of: the
         * major and the minor number
         *
         * @var array{int, int}|null
         */
        public ?array $version = null,
    ) {}
}
