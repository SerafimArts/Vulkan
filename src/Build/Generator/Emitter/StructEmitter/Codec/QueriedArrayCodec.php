<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `T*` of numbers, enums or structures that Vulkan fills after the arrays are
 * allocated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueriedArrayCodec extends AbstractPropertyCodec
{
    public function __construct(
        private TypeResolver $types,
        private NumberTypes $numbers,
    ) {}

    public function createReadExpression(PropertyContext $context): string
    {
        $value = $context->createMemberExpression();
        $length = $context->createLengthExpression();
        $type = $context->property->field->type;

        return match ($this->types->resolve($type)) {
            TypeKind::STRUCTURE => 'self::createStructureListFromCData(' . $value . ', ' . $length . ', '
                . Imports::import($context->namespace, Locations::STRUCT_NAMESPACE, $type) . '::createFromCData(...))',
            TypeKind::ENUMERATION => 'self::createEnumerationListFromCData('
                . Imports::import($context->namespace, Locations::ENUM_NAMESPACE, $type) . '::class, ' . $value
                . ', ' . $length . ')',
            default => 'self::createListFromCData(' . $value . ', ' . $length . ')',
        };
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'CData|null';
    }

    public function createDocType(PropertyContext $context): string
    {
        $type = $context->property->field->type;

        return match ($this->types->resolve($type)) {
            TypeKind::STRUCTURE, TypeKind::ENUMERATION => $this->createListType($context),
            default => 'list<' . $this->numbers->create($context->property) . '>',
        };
    }

    public function importElementClass(PropertyContext $context): void
    {
        $space = match ($this->types->resolve($context->property->field->type)) {
            TypeKind::STRUCTURE => Locations::STRUCT_NAMESPACE,
            TypeKind::ENUMERATION => Locations::ENUM_NAMESPACE,
            default => null,
        };

        if ($space !== null) {
            $this->importFrom($context, $space);
        }
    }
}
