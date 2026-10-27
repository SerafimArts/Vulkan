<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructDefinition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * @var list<FieldDefinition>
         */
        public array $members,
        /**
         * Names of the structures it can be chained to with `pNext`
         *
         * @var list<non-empty-string>
         */
        public array $extends = [],
    ) {}
}
