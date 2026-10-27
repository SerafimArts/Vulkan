<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

/**
 * Property of a generated structure that stands for a member of the C one.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class PropertyPlan
{
    public function __construct(
        public FieldDefinition $field,
        public PropertyKind $kind,
        public string $name,
        /**
         * Native PHP type, {@see null} if the type is a class (an enum or a
         * structure)
         *
         * @var non-empty-string|null
         */
        public ?string $builtinType,
        public bool $isNullable = false,
        public bool $hasDefault = false,
        public mixed $default = null,
        /**
         * Length of the C array
         *
         * @var int<0, max>
         */
        public int $arrayLength = 0,
    ) {}
}
