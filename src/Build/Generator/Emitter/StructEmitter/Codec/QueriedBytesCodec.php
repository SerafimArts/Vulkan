<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `void*` of the bytes that Vulkan fills after the buffer is allocated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueriedBytesCodec extends AbstractPropertyCodec
{
    public function createReadExpression(PropertyContext $context): string
    {
        return 'self::createBinaryFromCData(' . $context->createMemberExpression() . ', '
            . $context->createLengthExpression() . ')';
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'CData|null';
    }
}
