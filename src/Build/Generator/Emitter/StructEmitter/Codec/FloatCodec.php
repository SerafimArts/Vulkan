<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * A floating point number.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FloatCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return [$context->createMemberExpression() . ' = ' . $context->createValueExpression() . ';'];
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'float';
    }
}
