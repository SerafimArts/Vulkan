<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `VkBool32`.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BooleanCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return [$context->createMemberExpression() . ' = (int) ' . $context->createValueExpression() . ';'];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return $context->createMemberExpression() . ' !== 0';
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'int<0, 1>';
    }
}
