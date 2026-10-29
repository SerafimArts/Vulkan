<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * `char[N]`.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class TextCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $context->namespace->addUse(InvalidStructureException::class);

        $property = $context->property;
        $value = $context->createValueExpression();
        $size = (string) $property->arrayLength;

        return [
            'if (\strlen(' . $value . ') >= ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($context->structure, true) . ', ' . \var_export($property->name, true) . ', ' . $size
                . ' - 1);',
            '}',
            '',
            '\FFI::memcpy(' . $context->createMemberExpression() . ', ' . $value . ' . "\0", \strlen(' . $value
                . ') + 1);',
        ];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return '\FFI::string(' . $context->createMemberExpression() . ')';
    }
}
