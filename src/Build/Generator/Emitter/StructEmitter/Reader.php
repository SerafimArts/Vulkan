<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * Reading of the members of a native struct to the properties.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Reader
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * Returns the expression that reads the member of `$data`.
     */
    public function createExpression(PhpNamespace $namespace, PropertyPlan $property): string
    {
        $value = '$data->' . $property->field->name;

        return match ($property->kind) {
            PropertyKind::BOOLEAN => $value . ' !== 0',
            PropertyKind::ENUMERATION => Imports::import($namespace, Locations::ENUM_NAMESPACE, $property->field->type)
                . '::from(' . $value . ')',
            PropertyKind::TEXT => '\FFI::string(' . $value . ')',
            PropertyKind::BYTES => '\FFI::string(' . $value . ', ' . $property->arrayLength . ')',
            PropertyKind::NUMBER_LIST => 'self::createListFromCData(' . $value . ', ' . $property->arrayLength . ')',
            PropertyKind::STRUCTURE_LIST => 'self::createStructureListFromCData(' . $value . ', '
                . $property->arrayLength . ', '
                . Imports::import($namespace, Locations::STRUCT_NAMESPACE, $property->field->type)
                . '::createFromCData(...))',
            PropertyKind::STRUCTURE_VALUE => Imports::import(
                $namespace,
                Locations::STRUCT_NAMESPACE,
                $property->field->type,
            ) . '::createFromCData(' . $value . ')',
            default => $value,
        };
    }

    /**
     * Returns the type of the member of the native struct.
     */
    public function createType(PropertyPlan $property): string
    {
        return match ($property->kind) {
            PropertyKind::INTEGER => $this->types->getIntegerRange($property->field->type),
            PropertyKind::FLOAT => 'float',
            PropertyKind::BOOLEAN, PropertyKind::ENUMERATION => 'int',
            default => 'CData',
        };
    }
}
