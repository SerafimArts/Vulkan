<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `T[N]` of enums.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerationListCodec extends FixedListCodec
{
    public function createReadExpression(PropertyContext $context): string
    {
        return 'self::createEnumerationListFromCData('
            . Imports::import($context->namespace, Locations::ENUM_NAMESPACE, $context->property->field->type)
            . '::class, ' . $context->createMemberExpression() . ', ' . $context->createListLengthExpression() . ')';
    }

    protected function createBodyLines(PropertyContext $context): array
    {
        return [$context->createMemberExpression() . '[$i] = $item->value;'];
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
