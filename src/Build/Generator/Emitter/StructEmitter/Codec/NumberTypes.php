<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;

/**
 * The PHP types of the elements of the arrays of numbers.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NumberTypes
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * Returns the type of the elements of a list of numbers.
     */
    public function create(PropertyPlan $property): string
    {
        if (\in_array($property->field->type, ['float', 'double'], true)) {
            return 'float';
        }

        return $this->types->getIntegerRange($property->field->type);
    }
}
