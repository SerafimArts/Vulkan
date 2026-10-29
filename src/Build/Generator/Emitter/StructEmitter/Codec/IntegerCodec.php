<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\IntegerTypes;

/**
 * An integer or flags.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class IntegerCodec extends AbstractPropertyCodec
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * The length of the arrays is written by the arrays when they are not
     * empty, the value of the property is the length otherwise.
     */
    public function createWriteLines(PropertyContext $context): array
    {
        $field = $context->createMemberExpression();
        $value = $context->createValueExpression();

        if (!$context->isLength) {
            return [$field . ' = ' . $value . ';'];
        }

        return [
            'if (' . $value . ' !== 0) {',
            '    ' . $field . ' = ' . $value . ';',
            '}',
        ];
    }

    public function createNativeType(PropertyContext $context): string
    {
        return $this->types->getIntegerRange($context->property->field->type);
    }

    public function createDocType(PropertyContext $context): string
    {
        return IntegerTypes::create(
            $context->namespace,
            $this->types,
            $context->property->field->type,
            $context->property->field->bits,
        );
    }
}
