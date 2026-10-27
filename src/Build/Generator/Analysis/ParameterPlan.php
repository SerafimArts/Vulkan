<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ParameterPlan
{
    public function __construct(
        public FieldDefinition $field,
        public ParameterRole $role,
        public string $name,
        public TypeKind $kind,
        /**
         * Name of the parameter of the PHP method whose number of elements is
         * this one, for the length of an input array
         */
        public ?string $counts = null,
    ) {}
}
