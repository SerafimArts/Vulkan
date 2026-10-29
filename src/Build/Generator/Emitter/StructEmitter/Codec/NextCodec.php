<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `pNext`, the next structure of the chain.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NextCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $value = $context->createValueExpression();

        return [
            'if (' . $value . ' !== null) {',
            '    \assert(' . $value . ' instanceof InputStructureInterface);',
            '',
            '    $data->pNext = $memory->ffi->cast(\'void*\', \FFI::addr($memory->toStructure(' . $value . ')));',
            '}',
        ];
    }
}
