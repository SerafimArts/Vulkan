<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `T` of a structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructureValueCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return [$context->createValueExpression() . '->write($memory, ' . $context->createMemberExpression() . ');'];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        $class = Imports::import($context->namespace, Locations::STRUCT_NAMESPACE, $context->property->field->type);

        if ($context->isContextual) {
            return $class . '::createFromCData(' . $context->createMemberExpression() . ', vk: $vk, parent: $parent)';
        }

        return $class . '::createFromCData(' . $context->createMemberExpression() . ')';
    }
}
