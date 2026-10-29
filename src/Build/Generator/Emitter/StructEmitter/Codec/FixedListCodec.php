<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * An array of the fixed size: its elements are written one by one.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
abstract readonly class FixedListCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return $this->createListLines($context, $this->createBodyLines($context));
    }

    /**
     * Returns the lines that write the element `$item` with the index `$i`.
     *
     * @return list<string>
     */
    abstract protected function createBodyLines(PropertyContext $context): array;

    /**
     * Writes the elements of an array of the fixed size.
     *
     * @param list<string> $body the lines that write the element `$item` with the index `$i`
     *
     * @return list<string>
     */
    final protected function createListLines(PropertyContext $context, array $body): array
    {
        $context->namespace->addUse(InvalidStructureException::class);

        $value = $context->createValueExpression();
        $size = (string) $context->property->arrayLength;

        return [
            'if (\count(' . $value . ') > ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($context->structure, true) . ', ' . \var_export($context->property->name, true) . ', '
                . $size . ');',
            '}',
            '',
            'foreach (' . $value . ' as $i => $item) {',
            ...\array_map(static fn(string $line): string => '    ' . $line, $body),
            '}',
        ];
    }
}
