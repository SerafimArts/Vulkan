<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `T[N]` of handles.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleListCodec extends AbstractPropertyCodec
{
    public function createReadExpression(PropertyContext $context): string
    {
        return 'self::createHandleListFromCData('
            . Imports::import($context->namespace, Locations::HANDLE_NAMESPACE, $context->property->field->type)
            . '::class, ' . $context->createMemberExpression() . ', ' . $context->createListLengthExpression()
            . ', $vk, $parent)';
    }

    public function createDocType(PropertyContext $context): string
    {
        return $this->createListType($context);
    }

    public function importElementClass(PropertyContext $context): void
    {
        $this->importFrom($context, Locations::HANDLE_NAMESPACE);
    }
}
