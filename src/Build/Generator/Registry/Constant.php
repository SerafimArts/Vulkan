<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * A `#define` constant of the specification.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Constant
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * The PHP expression of the value
         *
         * @var non-empty-string
         */
        public string $expression,
        /**
         * The source of the definition in C
         *
         * @var non-empty-string
         */
        public string $definition,
        /**
         * Comment of the registry
         */
        public ?string $comment = null,
    ) {}
}
