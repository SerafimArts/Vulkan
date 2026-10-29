<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `T[N]` of numbers. A multidimensional native array is written and read as a flat
 * one.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NumberListCodec extends FixedListCodec
{
    public function __construct(
        private NumberTypes $numbers,
    ) {}

    public function createWriteLines(PropertyContext $context): array
    {
        $property = $context->property;

        if (!$property->field->isMultidimensional) {
            return parent::createWriteLines($context);
        }

        return [
            '$flat = $memory->ffi->cast(\'' . $property->field->type . '[' . $property->arrayLength . ']\', '
                . $context->createMemberExpression() . ');',
            '',
            ...parent::createWriteLines($context),
        ];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        $property = $context->property;
        $value = $context->createMemberExpression();

        if ($property->field->isMultidimensional) {
            $value = '\FFI::cast(\'' . $property->field->type . '[' . $property->arrayLength . ']\', ' . $value . ')';
        }

        return 'self::createListFromCData(' . $value . ', ' . $property->arrayLength . ')';
    }

    protected function createBodyLines(PropertyContext $context): array
    {
        if (!$context->property->field->isMultidimensional) {
            return [$context->createMemberExpression() . '[$i] = $item;'];
        }

        return [
            '// @phpstan-ignore-next-line the flat view of the native array is a number list',
            '$flat[$i] = $item;',
        ];
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<' . $this->numbers->create($context->property) . '>';
    }
}
