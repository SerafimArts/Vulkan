<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `const T*` of structures with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructureArrayCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        $method = 'toStructureArray';

        if ($context->property->field->pointerDepth === 2) {
            $method = 'toStructurePointerArray';
        }

        return '$memory->' . $method . '(' . $context->createValueExpression() . ')';
    }

    public function createDocType(PropertyContext $context): string
    {
        return $this->createListType($context);
    }

    public function importElementClass(PropertyContext $context): void
    {
        $this->importFrom($context, Locations::STRUCT_NAMESPACE);
    }
}
