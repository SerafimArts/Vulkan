<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\DocComments;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\IntegerTypes;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Emitter\MetaRecorder;
use Serafim\Vulkan\Build\Generator\Registry\Constant;

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
        private MetaRecorder $meta = new MetaRecorder(),
    ) {}

    /**
     * @param list<PropertyPlan> $properties
     * @param list<non-empty-string> $chains the structures whose chains `pNext`
     *        accepts
     * @param bool $isReturned whether the structure is read from native memory:
     *        a string that Vulkan has written may be empty
     */
    public function emit(
        PhpNamespace $namespace,
        ClassType $class,
        array $properties,
        array $chains = [],
        bool $isReturned = false,
    ): void {
        $constructor = $class->addMethod('__construct');

        $counted = [];

        foreach ($properties as $property) {
            if ($property->field->length !== null) {
                $counted[$property->field->length][] = '`$' . $property->name . '`';
            }
        }

        foreach (\array_values($properties) as $index => $property) {
            $this->recordBits($class, $property, $index, $isReturned);

            $parameter = $constructor->addPromotedParameter($property->name);

            if ($property->hasDefault) {
                $parameter = $constructor->addPromotedParameter($property->name, $this->createDefault($property));
            }

            $parameter->setPublic();

            if ($property->kind === PropertyKind::CHAIN) {
                $parameter->setType('array');
                $parameter->addComment('@var list<' . $this->createMarker($namespace, $chains[0] ?? '') . '>');

                continue;
            }

            if ($property->kind === PropertyKind::NEXT) {
                $parameter->setType($this->createChainType($namespace, $chains));
            } else {
                $parameter->setType($this->createType($namespace, $property));
                $parameter->setNullable($property->isNullable);
            }

            $description = DocComments::createLines($property->field->comment ?? '');
            $limit = $this->createLimitDescription($property);

            if (!$isReturned && isset($counted[$property->field->name])) {
                $limit = [...$limit, ...DocComments::createLines(
                    'The number of the elements of ' . \implode(', ', $counted[$property->field->name])
                    . ', it follows the array when it is given.',
                )];
            }

            if ($description !== [] && $limit !== []) {
                $description[] = '';
            }

            $description = [...$description, ...$limit];

            foreach ($description as $line) {
                $parameter->addComment($line);
            }

            $docType = $this->createDocType($namespace, $property, $isReturned);

            if ($docType !== null && $property->isNullable) {
                $docType .= '|null';
            }

            $this->importElementClass($namespace, $property);

            if ($docType !== null && $docType !== $property->builtinType) {
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
     * Records the bits that the argument of the constructor can be made of.
     *
     * @param int<0, max> $index
     */
    private function recordBits(ClassType $class, PropertyPlan $property, int $index, bool $isReturned): void
    {
        if ($isReturned) {
            return;
        }

        $bits = $this->findArgumentSet($class, $property);

        if ($bits !== null && $class->getName() !== null) {
            $this->meta->recordArgument(
                Locations::createClassName(Locations::STRUCT_NAMESPACE, $class->getName()),
                '__construct',
                $index,
                $bits,
            );
        }
    }

    /**
     * Returns the name of the set of the values that the argument is made of:
     * the bits of flags, the versions of the API, the names of the extensions.
     *
     * @return non-empty-string|null
     */
    private function findArgumentSet(ClassType $class, PropertyPlan $property): ?string
    {
        if ($property->kind === PropertyKind::STRING_LIST && $property->field->name === 'ppEnabledExtensionNames') {
            if (\str_contains($class->getName() ?? '', 'Instance')) {
                return Constant::INSTANCE_EXTENSIONS;
            }

            return Constant::DEVICE_EXTENSIONS;
        }

        if ($property->kind !== PropertyKind::INTEGER) {
            return null;
        }

        if ($property->field->name === 'apiVersion') {
            return Constant::API_VERSIONS;
        }

        return $this->types->findFlagBits($property->field->type);
    }

    private function createMarker(PhpNamespace $namespace, string $root): string
    {
        return Imports::import($namespace, Locations::EXTENSION_NAMESPACE, ExtensionEmitter::createName($root));
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
        if ($property->kind === PropertyKind::NATIVE) {
            $namespace->addUse(CData::class);
        }

        if ($property->builtinType !== null) {
            return $property->builtinType;
        }

        $space = match ($property->kind) {
            PropertyKind::ENUMERATION, PropertyKind::SCALAR_POINTER => Locations::ENUM_NAMESPACE,
            PropertyKind::HANDLE, PropertyKind::HANDLE_POINTER => Locations::HANDLE_NAMESPACE,
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
            PropertyKind::HANDLE_ARRAY, PropertyKind::HANDLE_LIST => Locations::HANDLE_NAMESPACE,
            PropertyKind::ENUMERATION_ARRAY, PropertyKind::ENUMERATION_LIST => Locations::ENUM_NAMESPACE,
            default => null,
        };

        if ($space !== null) {
            Imports::import($namespace, $space, $property->field->type);
        }
    }

    /**
     * Returns the signature of the function Vulkan calls: the pointers are
     * given as they are.
     */
    private function createCallbackType(PropertyPlan $property): ?string
    {
        $pointer = $this->types->findFunctionPointer($property->field->type);

        if ($pointer === null) {
            return null;
        }

        $parameters = [];

        foreach ($pointer->parameters as $parameter) {
            $parameters[] = $this->createCallbackValueType($parameter->type, $parameter->pointerDepth);
        }

        $return = 'void';

        if ($pointer->returnType !== 'void' || $pointer->returnPointerDepth > 0) {
            $return = $this->createCallbackValueType($pointer->returnType, $pointer->returnPointerDepth);
        }

        if (\str_contains($return, '|')) {
            $return = '(' . $return . ')';
        }

        return '\Closure(' . \implode(', ', $parameters) . '): ' . $return;
    }

    private function createCallbackValueType(string $type, int $pointerDepth): string
    {
        if ($pointerDepth > 0) {
            return 'CData|null';
        }

        return match ($this->types->resolve($type)) {
            TypeKind::BOOLEAN => 'bool|int',
            TypeKind::FLOAT => 'float',
            TypeKind::INTEGER, TypeKind::ENUMERATION => 'int',
            default => 'CData',
        };
    }

    private function createScalarPointerType(PhpNamespace $namespace, PropertyPlan $property): ?string
    {
        if ($property->builtinType === 'int') {
            return IntegerTypes::create($namespace, $this->types, $property->field->type);
        }

        return null;
    }

    private function createStringType(PropertyPlan $property, bool $isReturned): ?string
    {
        if ($property->field->isOptional || $isReturned) {
            return null;
        }

        return 'non-empty-string';
    }

    private function createDocType(PhpNamespace $namespace, PropertyPlan $property, bool $isReturned): ?string
    {
        return match ($property->kind) {
            PropertyKind::INTEGER => IntegerTypes::create(
                $namespace,
                $this->types,
                $property->field->type,
                $property->field->bits,
            ),
            PropertyKind::SCALAR_POINTER => $this->createScalarPointerType($namespace, $property),
            PropertyKind::STRING => $this->createStringType($property, $isReturned),
            PropertyKind::CALLBACK => $this->createCallbackType($property),
            PropertyKind::STRING_LIST => 'list<string>',
            PropertyKind::NUMBER_LIST,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::SAMPLE_MASK => 'list<' . $this->createNumberType($property) . '>',
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::STRUCTURE_LIST,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::HANDLE_LIST,
            PropertyKind::ENUMERATION_LIST,
            PropertyKind::ENUMERATION_ARRAY => 'list<' . $property->field->type . '>',
            default => null,
        };
    }
}
