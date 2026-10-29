<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `T*` of an enum that Vulkan fills: the place for it is allocated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FeedbackEnumerationCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $buffer = '$this->' . $context->property->createBufferName();

        return [
            $buffer . ' = $memory->new(' . \var_export($context->property->field->type, true) . ');',
            $context->createMemberExpression() . ' = \FFI::addr(' . $buffer . ');',
        ];
    }
}
