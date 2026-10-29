<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `const T*` of one handle.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandlePointerCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $value = $context->createValueExpression();
        $type = $context->property->field->type;

        return [
            'if (' . $value . ' !== null) {',
            '    ' . $context->createMemberExpression() . ' = $memory->ffi->cast(' . \var_export($type . '*', true)
                . ', $memory->toHandleArray(' . \var_export($type, true) . ', [' . $value . ']));',
            '}',
        ];
    }

    protected function getNamespace(): string
    {
        return Locations::HANDLE_NAMESPACE;
    }
}
