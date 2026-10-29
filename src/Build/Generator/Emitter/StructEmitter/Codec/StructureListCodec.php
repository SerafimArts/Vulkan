<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `T[N]` of structures.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructureListCodec extends FixedListCodec
{
    public function createReadExpression(PropertyContext $context): string
    {
        $property = $context->property;
        $value = $context->createMemberExpression();
        $class = Imports::import($context->namespace, Locations::STRUCT_NAMESPACE, $property->field->type);

        if ($context->isContextual) {
            return 'self::createStructureListFromCData(' . $value . ', ' . $property->arrayLength
                . ', fn(CData $element): ' . $class . ' => ' . $class . '::createFromCData($element, $vk, $parent))';
        }

        return 'self::createStructureListFromCData(' . $value . ', ' . $property->arrayLength . ', ' . $class
            . '::createFromCData(...))';
    }

    protected function createBodyLines(PropertyContext $context): array
    {
        return [
            '// @phpstan-ignore-next-line the element of the native array is a structure',
            '$item->write($memory, ' . $context->createMemberExpression() . '[$i]);',
        ];
    }

    public function createDocType(PropertyContext $context): string
    {
        return $this->createListType($context);
    }

    public function importElementClass(PropertyContext $context): void
    {
        $this->importFrom($context, Locations::STRUCT_NAMESPACE);
    }
}
