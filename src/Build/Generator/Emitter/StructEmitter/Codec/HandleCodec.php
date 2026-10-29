<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * A handle.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return $this->createNullableLines(
            $context,
            $context->createMemberExpression() . ' = $memory->ffi->cast('
                . \var_export($context->property->field->type, true) . ', ' . $context->createValueExpression()
                . '->pointer);',
        );
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return 'self::createHandleFromCData('
            . Imports::import($context->namespace, Locations::HANDLE_NAMESPACE, $context->property->field->type)
            . '::class, ' . $context->createMemberExpression() . ', $vk, $parent)';
    }

    protected function getNamespace(): string
    {
        return Locations::HANDLE_NAMESPACE;
    }
}
