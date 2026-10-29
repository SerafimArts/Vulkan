<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const void*` or `const uint32_t*` of bytes with the size in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BinaryCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $value = $context->createValueExpression();

        return [
            'if (' . $value . ' !== \'\') {',
            '    ' . $context->createLengthExpression() . ' = \strlen(' . $value . ');',
            '    ' . $context->createMemberExpression() . ' = $memory->ffi->cast('
                . \var_export($context->property->field->type . '*', true)
                . ', $memory->toCString(' . $value . '));',
            '}',
        ];
    }
}
