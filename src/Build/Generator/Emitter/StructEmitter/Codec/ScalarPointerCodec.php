<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\IntegerTypes;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `const T*` of one number or enum: the pointer to the copy of the number is
 * written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ScalarPointerCodec extends AbstractPropertyCodec
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    public function createWriteLines(PropertyContext $context): array
    {
        $property = $context->property;
        $value = $context->createValueExpression();
        $number = $value;

        if ($property->builtinType === null) {
            $number = $value . '->value';
        }

        if ($property->builtinType === 'bool') {
            $number = '(int) ' . $value;
        }

        return [
            'if (' . $value . ' !== null) {',
            '    ' . $context->createMemberExpression() . ' = $memory->ffi->cast(\''
                . $property->field->type . '*\', $memory->toNumberArray(\''
                . $property->field->type . '\', [' . $number . ']));',
            '}',
        ];
    }

    protected function getNamespace(): string
    {
        return Locations::ENUM_NAMESPACE;
    }

    public function createDocType(PropertyContext $context): ?string
    {
        if ($context->property->builtinType === 'int') {
            return IntegerTypes::create($context->namespace, $this->types, $context->property->field->type);
        }

        return null;
    }
}
