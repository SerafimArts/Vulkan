<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const T*` of a structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructurePointerCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $value = $context->createValueExpression();

        return [
            'if (' . $value . ' !== null) {',
            '    ' . $context->createMemberExpression() . ' = \FFI::addr($memory->toStructure(' . $value . '));',
            '}',
        ];
    }
}
