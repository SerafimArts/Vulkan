<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use FFI\CData;

/**
 * A value of the system or a pointer that the user gives as it is: the number is
 * turned to the type of the member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NativeCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $field = $context->property->field;
        $type = $field->type . \str_repeat('*', $field->pointerDepth);

        return $this->createNullableLines(
            $context,
            $context->createMemberExpression() . ' = $memory->ffi->cast(' . \var_export($type, true) . ', '
                . $context->createValueExpression() . ');',
        );
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'CData|int|null';
    }

    public function createPhpType(PropertyContext $context): string
    {
        $context->namespace->addUse(CData::class);

        return parent::createPhpType($context);
    }
}
