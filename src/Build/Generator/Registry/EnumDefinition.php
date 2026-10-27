<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumDefinition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * Values by the names of the cases
         *
         * @var array<non-empty-string, int>
         */
        public array $cases,
        /**
         * Names of the cases by the names of their aliases
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $aliases,
        /**
         * Comments of the registry by the names of the cases
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $comments = [],
        /**
         * Names of the deprecated cases and aliases
         *
         * @var array<non-empty-string, true>
         */
        public array $deprecated = [],
        /**
         * Whether the values are the bits of flags: such a type is not an
         * enumeration, its values are combined
         */
        public bool $isFlags = false,
    ) {}
}
