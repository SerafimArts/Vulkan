<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `const T*` of enums with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerationArrayCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        $type = $context->property->field->type;

        return '$memory->toNumberArray(' . \var_export($type, true) . ', \array_map(static fn('
            . Imports::import($context->namespace, Locations::ENUM_NAMESPACE, $type)
            . ' $item): int => $item->value, ' . $context->createValueExpression() . '))';
    }

    public function createDocType(PropertyContext $context): string
    {
        return $this->createListType($context);
    }

    public function importElementClass(PropertyContext $context): void
    {
        $this->importFrom($context, Locations::ENUM_NAMESPACE);
    }
}
