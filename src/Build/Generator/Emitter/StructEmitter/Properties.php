<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\DocComments;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\IntegerTypes;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * The properties of a generated structure: the promoted parameters of the
 * constructor with their types and docblocks.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Properties
{
    /**
     * How the limits of a device are compared with the required ones. The type
     * is the one of the limit: the device reports the maximum it supports for
     * a `max` limit, the minimum for a `min` limit.
     */
    private const array LIMITS = [
        'max' => 'The value is at least the required one.',
        'min' => 'The value is at most the required one.',
        'bits' => 'The value has at least the required bits set.',
        'pot' => 'The value is a power of two.',
        'mul' => 'The value is a multiple of the required one.',
        'range' => 'The range covers at least the required one.',
        'exact' => 'The value is exactly the required one.',
    ];

    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * @param list<PropertyPlan> $properties
     * @param list<non-empty-string> $chains the structures whose chains `pNext`
     *        accepts
     */
    public function emit(PhpNamespace $namespace, ClassType $class, array $properties, array $chains = []): void
    {
        $constructor = $class->addMethod('__construct');

        foreach ($properties as $property) {
            $parameter = $constructor->addPromotedParameter($property->name);

            if ($property->hasDefault) {
                $parameter = $constructor->addPromotedParameter($property->name, $this->createDefault($property));
            }

            $parameter->setPublic();

            if ($property->kind === PropertyKind::NEXT) {
                $parameter->setType($this->createChainType($namespace, $chains));
            } else {
                $parameter->setType($this->createType($namespace, $property));
                $parameter->setNullable($property->isNullable);
            }

            $description = DocComments::createLines($property->field->comment ?? '');
            $limit = $this->createLimitDescription($property);

            if ($description !== [] && $limit !== []) {
                $description[] = '';
            }

            $description = [...$description, ...$limit];

            foreach ($description as $line) {
                $parameter->addComment($line);
            }

            $docType = $this->createDocType($namespace, $property);

            $this->importElementClass($namespace, $property);

            if ($docType !== null) {
                if ($description !== []) {
                    $parameter->addComment('');
                }

                $parameter->addComment('@var ' . $docType);
            }
        }
    }

    /**
     * Puts the properties without a default first.
     *
     * @param list<PropertyPlan> $properties
     *
     * @return list<PropertyPlan>
     */
    public function sortRequiredFirst(array $properties): array
    {
        $required = \array_filter($properties, static fn(PropertyPlan $property): bool => !$property->hasDefault);
        $optional = \array_filter($properties, static fn(PropertyPlan $property): bool => $property->hasDefault);

        return [...\array_values($required), ...\array_values($optional)];
    }

    /**
     * Returns the type of the elements of a list of numbers.
     */
    public function createNumberType(PropertyPlan $property): string
    {
        if (\in_array($property->field->type, ['float', 'double'], true)) {
            return 'float';
        }

        return $this->types->getIntegerRange($property->field->type);
    }

    /**
     * @param list<non-empty-string> $chains
     */
    private function createChainType(PhpNamespace $namespace, array $chains): string
    {
        $types = [];

        foreach ($chains as $root) {
            $types[] = Imports::importFullName(
                $namespace,
                Locations::EXTENSION_NAMESPACE,
                ExtensionEmitter::createName($root),
            );
        }

        return \implode('|', $types) . '|null';
    }

    /**
     * Tells how the limit of a device is compared with the required one.
     *
     * @return list<string>
     */
    private function createLimitDescription(PropertyPlan $property): array
    {
        $sentences = [];

        foreach (\explode(',', $property->field->limitType ?? '') as $type) {
            $sentence = self::LIMITS[$type] ?? null;

            if ($sentence !== null) {
                $sentences[] = $sentence;
            }
        }

        if ($sentences === []) {
            return [];
        }

        return DocComments::createLines(\implode(' ', $sentences));
    }

    private function createDefault(PropertyPlan $property): mixed
    {
        if ($property->kind === PropertyKind::ENUMERATION && \is_string($property->default)) {
            return new Literal($property->field->type . '::' . $property->default);
        }

        return $property->default;
    }

    private function createType(PhpNamespace $namespace, PropertyPlan $property): string
    {
        if ($property->builtinType !== null) {
            return $property->builtinType;
        }

        $space = match ($property->kind) {
            PropertyKind::ENUMERATION => Locations::ENUM_NAMESPACE,
            PropertyKind::HANDLE => Locations::HANDLE_NAMESPACE,
            default => Locations::STRUCT_NAMESPACE,
        };

        Imports::import($namespace, $space, $property->field->type);

        return Locations::createClassName($space, $property->field->type);
    }

    /**
     * Imports the class of the elements of an array, the docblock refers to it.
     */
    private function importElementClass(PhpNamespace $namespace, PropertyPlan $property): void
    {
        $space = match ($property->kind) {
            PropertyKind::STRUCTURE_ARRAY, PropertyKind::STRUCTURE_LIST => Locations::STRUCT_NAMESPACE,
            PropertyKind::HANDLE_ARRAY => Locations::HANDLE_NAMESPACE,
            PropertyKind::ENUMERATION_ARRAY => Locations::ENUM_NAMESPACE,
            default => null,
        };

        if ($space !== null) {
            Imports::import($namespace, $space, $property->field->type);
        }
    }

    private function createStringType(PropertyPlan $property): ?string
    {
        if ($property->field->isOptional) {
            return null;
        }

        return 'non-empty-string|null';
    }

    private function createDocType(PhpNamespace $namespace, PropertyPlan $property): ?string
    {
        return match ($property->kind) {
            PropertyKind::INTEGER => IntegerTypes::create($namespace, $this->types, $property->field->type),
            PropertyKind::STRING => $this->createStringType($property),
            PropertyKind::STRING_LIST => 'list<string>',
            PropertyKind::NUMBER_LIST,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::SAMPLE_MASK => 'list<' . $this->createNumberType($property) . '>',
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::STRUCTURE_LIST,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::ENUMERATION_ARRAY => 'list<' . $property->field->type . '>',
            default => null,
        };
    }
}
