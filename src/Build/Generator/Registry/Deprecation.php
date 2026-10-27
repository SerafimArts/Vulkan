<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * The reason why a command or a type of the specification is deprecated, or
 * is legacy: it is still supported, but a newer way to do the same exists.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Deprecation
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $description,
        /**
         * Name of the command that replaces the deprecated one
         *
         * @var non-empty-string|null
         */
        public ?string $supersededBy = null,
        /**
         * Name of the section of the specification that explains it
         *
         * @var non-empty-string|null
         */
        public ?string $link = null,
        /**
         * Whether it is legacy functionality, which is fully supported
         */
        public bool $isLegacy = false,
    ) {}
}
