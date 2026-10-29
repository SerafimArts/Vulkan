<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `size_t*` that Vulkan reads and writes: the pointer to the copy of the size
 * is written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class InoutIntegerCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $buffer = '$this->' . $context->property->createBufferName();
        $type = $context->property->field->type;

        return [
            $buffer . ' = $memory->toNumberArray(' . \var_export($type, true) . ', ['
                . $context->createValueExpression() . ']);',
            $context->createMemberExpression() . ' = $memory->ffi->cast(' . \var_export($type . '*', true) . ', '
                . $buffer . ');',
        ];
    }

    public function createPhpType(PropertyContext $context): string
    {
        return 'int';
    }
}
