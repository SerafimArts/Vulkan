<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\Names;

/**
 * `T*` of returned structures that Vulkan fills: as many as the user has asked
 * for are allocated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FeedbackArrayCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $property = $context->property;
        $buffer = '$this->' . $property->createBufferName();
        $count = '$this->' . Names::createVariableName($property->field->length ?? '');
        $type = $property->field->type;

        return [
            $buffer . ' = null;',
            '',
            'if (' . $count . ' !== 0) {',
            '    ' . $buffer . ' = $memory->new(\'' . $type . '[\' . ' . $count . ' . \']\');',
            '    ' . $context->createMemberExpression() . ' = $memory->ffi->cast(\'' . $type . '*\', ' . $buffer . ');',
            '}',
        ];
    }
}
