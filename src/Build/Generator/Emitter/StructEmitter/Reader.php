<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Internal\EnumCase;

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
    public function createExpression(PhpNamespace $namespace, PropertyPlan $property, bool $isContextual = false): string
    {
        $value = '$data->' . $property->field->name;

        if ($property->kind === PropertyKind::ENUMERATION) {
            $namespace->addUse(EnumCase::class);
        }

        if ($property->kind === PropertyKind::HANDLE) {
            return 'self::createHandleFromCData('
                . Imports::import($namespace, Locations::HANDLE_NAMESPACE, $property->field->type)
                . '::class, ' . $value . ', $vk, $parent)';
        }

        if ($property->kind === PropertyKind::ENUMERATION_LIST) {
            return 'self::createEnumerationListFromCData('
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $property->field->type)
                . '::class, ' . $value . ', ' . $this->createListLength($property) . ')';
        }

        if ($property->kind === PropertyKind::HANDLE_LIST) {
            return 'self::createHandleListFromCData('
                . Imports::import($namespace, Locations::HANDLE_NAMESPACE, $property->field->type)
                . '::class, ' . $value . ', ' . $this->createListLength($property) . ', $vk, $parent)';
        }

        if ($isContextual && $property->kind === PropertyKind::STRUCTURE_VALUE) {
            return Imports::import($namespace, Locations::STRUCT_NAMESPACE, $property->field->type)
                . '::createFromCData(' . $value . ', vk: $vk, parent: $parent)';
        }

        if ($isContextual && $property->kind === PropertyKind::STRUCTURE_LIST) {
            $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $property->field->type);

            return 'self::createStructureListFromCData(' . $value . ', ' . $property->arrayLength
                . ', fn(CData $element): ' . $class . ' => ' . $class . '::createFromCData($element, $vk, $parent))';
        }

        return match ($property->kind) {
            PropertyKind::BOOLEAN => $value . ' !== 0',
            PropertyKind::ENUMERATION => 'EnumCase::createFromValue('
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $property->field->type)
                . '::class, ' . $value . ')',
            PropertyKind::STRING => 'self::createStringFromCData(' . $value . ')',
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
     * Returns how many elements of the array are set: the structure counts
     * them in another member.
     */
    private function createListLength(PropertyPlan $property): string
    {
        $count = $property->field->length;

        if ($count === null) {
            return (string) $property->arrayLength;
        }

        return '\min($data->' . $count . ', ' . $property->arrayLength . ')';
    }

    /**
     * Returns the type of a member that is not a property: the length of an
     * array.
     */
    public function createFieldType(FieldDefinition $member): string
    {
        return $this->types->getIntegerRange($member->type);
    }

    /**
     * Returns the type of the member of the native struct.
     */
    public function createType(PhpNamespace $namespace, PropertyPlan $property): string
    {
        return match ($property->kind) {
            PropertyKind::INTEGER => $this->types->getIntegerRange($property->field->type),
            PropertyKind::NATIVE => 'CData|int|null',
            PropertyKind::STRING => 'CData|null',
            PropertyKind::FLOAT => 'float',
            PropertyKind::BOOLEAN => 'int<0, 1>',
            PropertyKind::ENUMERATION => 'value-of<'
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $property->field->type) . '>',
            default => 'CData',
        };
    }
}
