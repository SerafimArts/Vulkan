<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Exception\InvalidStructure\DifferentArrayLengthsException;

/**
 * An array with the length in another member: the length and the pointer are written
 * when the array is not empty.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
abstract readonly class ArrayCodec extends AbstractPropertyCodec
{
    /**
     * Writes the length and the pointer of an array that is not empty. The
     * arrays that share the length member must have the same length.
     */
    final public function createWriteLines(PropertyContext $context): array
    {
        $property = $context->property;
        $value = $context->createValueExpression();
        $length = $context->createLengthExpression();
        $lines = ['if (' . $value . ' !== []) {'];

        if ($context->isLengthShared) {
            $context->namespace->addUse(DifferentArrayLengthsException::class);

            \array_push(
                $lines,
                '    if (' . $length . ' !== 0 && ' . $length . ' !== \count(' . $value . ')) {',
                '        throw DifferentArrayLengthsException::becauseArraysHaveDifferentLengths('
                    . \var_export($context->structure, true) . ', ' . \var_export($property->name, true) . ');',
                '    }',
                '',
            );
        }

        \array_push(
            $lines,
            '    ' . $length . ' = \count(' . $value . ');',
            '    $data->' . $property->field->name . ' = $memory->ffi->cast('
                . \var_export($this->createPointerType($context), true) . ', ' . $this->createAllocation($context) . ');',
            '}',
        );

        return $lines;
    }

    /**
     * Returns the expression that allocates the native array.
     */
    abstract protected function createAllocation(PropertyContext $context): string;

    protected function createPointerType(PropertyContext $context): string
    {
        $field = $context->property->field;

        return $field->type . \str_repeat('*', \max(1, $field->pointerDepth));
    }
}
