<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Properties;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Reader;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Writer;
use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Internal\Memory\Allocation;
use Serafim\Vulkan\Struct\InputStructure;
use Serafim\Vulkan\Struct\OutputStructure;

/**
 * Writes a structure of the specification as a class: a mutable one that
 * writes itself to native memory, or an immutable one that is read from it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructEmitter
{
    private PhpFiles $files;

    private Properties $properties;

    private Reader $reader;

    private Writer $writer;

    private SpecificationTags $tags;

    public function __construct(
        private Registry $registry,
        TypeResolver $types,
    ) {
        $this->files = new PhpFiles();
        $this->properties = new Properties($types);
        $this->reader = new Reader($types);
        $this->writer = new Writer();
        $this->tags = new SpecificationTags($registry);
    }

    /**
     * @throws InvalidRegistryException in case of the structure type is not
     *         defined
     */
    public function emit(StructPlan $plan): GeneratedFile
    {
        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::STRUCT_NAMESPACE);

        $class = $namespace->addClass($plan->struct->name);

        $class->setFinal();

        foreach ($this->tags->createLines($plan->struct->name) as $line) {
            $class->addComment($line);
        }

        match ($plan->role) {
            StructRole::INPUT => $this->emitInput($namespace, $class, $plan),
            StructRole::OUTPUT => $this->emitOutput($namespace, $class, $plan),
            StructRole::DUAL => $this->emitDual($namespace, $class, $plan),
        };

        return new GeneratedFile(
            Locations::createPath('Struct', $plan->struct->name),
            $this->files->print($file),
        );
    }

    /**
     * @throws InvalidRegistryException
     */
    private function emitInput(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(InputStructure::class);
        $namespace->addUse(CData::class);
        $namespace->addUse(Allocation::class);

        $class->setExtends(InputStructure::class);

        $native = $class->addConstant('NATIVE_TYPE', $plan->struct->name);

        $native->setProtected();
        $native->setType('string');

        $this->properties->emit(
            $namespace,
            $class,
            $this->properties->sortRequiredFirst($plan->properties),
            $plan->chains,
        );

        foreach ($plan->implements as $root) {
            $class->addImplement(
                Imports::importFullName(
                    $namespace,
                    Locations::EXTENSION_NAMESPACE,
                    ExtensionEmitter::createName($root),
                ),
            );
        }

        $lines = [];

        if ($plan->structureType !== null) {
            $constant = $class->addConstant('STRUCTURE_TYPE', $this->getStructureType($plan->structureType));

            $constant->setPublic();
            $constant->setType('int');
            $constant->addComment('`' . $plan->structureType . '`');

            $lines[] = '$data->sType = self::STRUCTURE_TYPE;';
        }

        $names = $this->findPropertyFields($plan);
        $arrayLengths = $this->findLengths($plan);
        $lengths = [];

        foreach ($plan->properties as $property) {
            if ($lines !== []) {
                $lines[] = '';
            }

            $length = $this->findArrayLength($property);
            $isShared = $length !== null && (isset($lengths[$length]) || isset($names[$length]));
            $isLength = isset($arrayLengths[$property->field->name]);

            if ($length !== null) {
                $lengths[$length] = true;
            }

            \array_push(
                $lines,
                ...$this->writer->createLines($namespace, $property, $plan->struct->name, $isShared, $isLength),
            );
        }

        $write = $class->addMethod('write')
            ->setProtected()
            ->setReturnType('void')
            ->setBody(\implode("\n", $lines));

        $write->addParameter('memory')->setType(Allocation::class);
        $write->addParameter('data')->setType(CData::class);
    }

    /**
     * @throws InvalidRegistryException
     */
    private function emitDual(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $this->emitInput($namespace, $class, $plan);
        $this->emitFactory($namespace, $class, $plan);
    }

    private function emitOutput(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(OutputStructure::class);

        $class->setReadOnly();
        $class->setExtends(OutputStructure::class);

        if ($plan->structureType !== null) {
            $constant = $class->addConstant('STRUCTURE_TYPE', $this->getStructureType($plan->structureType));

            $constant->setPublic();
            $constant->setType('int');
            $constant->addComment('`' . $plan->structureType . '`');
        }

        $this->properties->emit($namespace, $class, $plan->properties);
        $this->emitFactory($namespace, $class, $plan);
    }

    /**
     * Returns the properties that are read from the native struct: the chain
     * of the structures is not read.
     *
     * @return list<PropertyPlan>
     */
    private function findReadProperties(StructPlan $plan): array
    {
        return \array_values(\array_filter(
            $plan->properties,
            static fn(PropertyPlan $property): bool => $property->kind !== PropertyKind::NEXT,
        ));
    }

    /**
     * Returns the members of the structure that are properties.
     *
     * @return array<string, true>
     */
    private function findPropertyFields(StructPlan $plan): array
    {
        $names = [];

        foreach ($plan->properties as $property) {
            $names[$property->field->name] = true;
        }

        return $names;
    }

    /**
     * Returns the length members of the arrays of the structure.
     *
     * @return array<string, true>
     */
    private function findLengths(StructPlan $plan): array
    {
        $lengths = [];

        foreach ($plan->properties as $property) {
            $length = $this->findArrayLength($property);

            if ($length !== null) {
                $lengths[$length] = true;
            }
        }

        return $lengths;
    }

    /**
     * Returns the name of the length member of the array property.
     */
    private function findArrayLength(PropertyPlan $property): ?string
    {
        $isArray = \in_array($property->kind, [
            PropertyKind::STRING_LIST,
            PropertyKind::BINARY,
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::ENUMERATION_ARRAY,
        ], true);

        if ($isArray) {
            return $property->field->length;
        }

        return null;
    }

    private function emitFactory(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(CData::class);

        $arguments = [];

        foreach ($this->findReadProperties($plan) as $property) {
            $isWide = $property->kind === PropertyKind::NUMBER_LIST
                && $this->properties->createNumberType($property) !== 'float';

            if ($isWide) {
                $arguments[] = '// @phpstan-ignore-next-line the elements of the array are of any width';
            }

            $arguments[] = $property->name . ': ' . $this->reader->createExpression($namespace, $property) . ',';
        }

        $body = "return new self(\n    " . \implode("\n    ", $arguments) . "\n);";

        if ($arguments === []) {
            $body = 'return new self();';
        }

        $method = $class->addMethod('createFromCData')
            ->setPublic()
            ->setStatic()
            ->setReturnType('self')
            ->setBody($body);

        $method->addParameter('data')->setType(CData::class);

        $shape = ['@param CData&object{'];

        foreach ($this->findReadProperties($plan) as $property) {
            $shape[] = '    ' . $property->field->name . ': ' . $this->reader->createType($property) . ',';
        }

        $shape[] = '} $data';
        $shape[] = '@phpstan-param CData $data';

        foreach ($shape as $line) {
            $method->addComment($line);
        }
    }

    /**
     * @throws InvalidRegistryException
     */
    private function getStructureType(string $name): int
    {
        return $this->registry->getEnum('VkStructureType')->cases[$name]
            ?? throw InvalidRegistryException::becauseDefinitionNotFound('structure type', $name);
    }
}
