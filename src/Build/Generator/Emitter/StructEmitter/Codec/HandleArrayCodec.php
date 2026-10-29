<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `const T*` of handles with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleArrayCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        return '$memory->toHandleArray(' . \var_export($context->property->field->type, true) . ', '
            . $context->createValueExpression() . ')';
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
