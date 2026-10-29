<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * `uint8_t[N]`, the bytes of an identifier.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BytesCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $context->namespace->addUse(InvalidStructureException::class);

        $property = $context->property;
        $value = $context->createValueExpression();
        $size = (string) $property->arrayLength;

        if ($property->field->pointerDepth === 1) {
            return [
                'if (\strlen(' . $value . ') !== ' . $size . ') {',
                '    throw InvalidStructureException::becauseArrayHasWrongLength('
                    . \var_export($context->structure, true) . ', ' . \var_export($property->name, true) . ', '
                    . $size . ');',
                '}',
                '',
                $context->createMemberExpression()
                    . ' = $memory->ffi->cast(\'uint8_t*\', $memory->toCString(' . $value . '));',
            ];
        }

        return [
            'if (\strlen(' . $value . ') > ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($context->structure, true) . ', ' . \var_export($property->name, true) . ', '
                . $size . ');',
            '}',
            '',
            'if (' . $value . ' !== \'\') {',
            '    \FFI::memcpy(' . $context->createMemberExpression() . ', ' . $value . ', \strlen(' . $value . '));',
            '}',
        ];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return '\FFI::string(' . $context->createMemberExpression() . ', ' . $context->property->arrayLength . ')';
    }
}
