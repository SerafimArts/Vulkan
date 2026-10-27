<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * A `PFN_*` type: the function that Vulkan calls.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FunctionPointer
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * The C type that the function returns
         *
         * @var non-empty-string
         */
        public string $returnType,
        /**
         * How many pointers the returned type has
         *
         * @var int<0, max>
         */
        public int $returnPointerDepth,
        /**
         * @var list<FieldDefinition>
         */
        public array $parameters,
    ) {}
}
