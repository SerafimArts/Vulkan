<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * Member of a structure or parameter of a command.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FieldDefinition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * Name of the C type without qualifiers
         *
         * @var non-empty-string
         */
        public string $type,
        /**
         * Number of `*` of the declaration
         *
         * @var int<0, max>
         */
        public int $pointerDepth,
        /**
         * Whether the value behind the pointer is `const`
         */
        public bool $isConst,
        /**
         * Size of the array: a number or the name of a constant
         */
        public ?string $arraySize = null,
        /**
         * Name of the field that holds the length of the array
         */
        public ?string $length = null,
        public bool $isNullTerminated = false,
        public bool $isOptional = false,
        /**
         * Fixed value of the field (`sType`)
         */
        public ?string $value = null,
        /**
         * Reason why the declaration cannot be described by the fields above
         */
        public ?string $unsupported = null,
        /**
         * Comment of the registry
         */
        public ?string $comment = null,
        /**
         * How the limit is compared with the required one, the value of `limittype`
         */
        public ?string $limitType = null,
        /**
         * How many elements one unit of the length member holds: the length
         * of `codeSize / 4` is counted in bytes, the elements are 4 bytes
         *
         * @var int<1, max>
         */
        public int $lengthDivisor = 1,
    ) {}
}
