<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const char*`.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StringCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $value = $context->createValueExpression();

        return [
            'if (' . $value . ' !== null) {',
            '    ' . $context->createMemberExpression() . ' = $memory->toCString(' . $value . ');',
            '}',
        ];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return 'self::createStringFromCData(' . $context->createMemberExpression() . ')';
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'CData|null';
    }

    public function createDocType(PropertyContext $context): ?string
    {
        if ($context->property->field->isOptional || $context->isReturned) {
            return null;
        }

        return 'non-empty-string';
    }
}
