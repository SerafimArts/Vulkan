<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
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
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Internal\Memory\Allocation;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Struct\InputStructure;
use Serafim\Vulkan\Struct\OutputStructure;
use Serafim\Vulkan\VulkanBase;

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

    /**
     * @param list<non-empty-string> $contextual the returned structures that
     *        hold handles
     */
    public function __construct(
        private Registry $registry,
        TypeResolver $types,
        private array $contextual = [],
        private MetaRecorder $meta = new MetaRecorder(),
    ) {
        $this->files = new PhpFiles();
        $this->properties = new Properties($types, $this->meta);
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
            $this->emitStructureType($namespace, $class, $plan->structureType);

            $lines[] = '$data->sType = self::STRUCTURE_TYPE;';
        }

        if ($plan->struct->isUnion) {
            $lines = $this->writer->createUnionLines($namespace, $plan);
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

            $propertyLines = $this->writer->createLines($namespace, $property, $plan->struct->name, $isShared, $isLength);

            if ($plan->struct->isUnion) {
                $propertyLines = $this->writer->createGuardedLines($property, $propertyLines);
            }

            \array_push($lines, ...$propertyLines);
        }

        $write = $class->addMethod('write')
            ->setProtected()
            ->setReturnType('void')
            ->setBody(\implode("\n", $lines));

        $write->addParameter('memory')->setType(Allocation::class);
        $write->addParameter('data')->setType(CData::class);

        foreach ($this->createShape($namespace, $plan) as $line) {
            $write->addComment($line);
        }
    }

    /**
     * Returns the lines of the docblock that describes the members of the
     * native struct the structure is written to.
     *
     * @return list<string>
     */
    private function createShape(PhpNamespace $namespace, StructPlan $plan): array
    {
        $properties = [];

        foreach ($plan->properties as $property) {
            $properties[$property->field->name] = $property;
        }

        $shape = ['@param CData&object{'];

        foreach ($plan->struct->members as $member) {
            $property = $properties[$member->name] ?? null;

            $type = match (true) {
                $property !== null => $this->reader->createType($namespace, $property),
                $member->name === 'sType' && $plan->structureType !== null => 'value-of<'
                    . Imports::import($namespace, Locations::ENUM_NAMESPACE, 'VkStructureType')
                    . '::' . $plan->structureType . '>',
                $member->name === 'sType' => 'int',
                $member->name === 'pNext' => 'CData',
                default => $this->reader->createFieldType($member),
            };

            $shape[] = '    ' . $member->name . ': ' . $type . ',';
        }

        $shape[] = '} $data';
        $shape[] = '@phpstan-param CData $data';

        return $shape;
    }

    /**
     * @throws InvalidRegistryException
     */
    private function emitDual(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $this->emitInput($namespace, $class, $plan);

        if ($plan->implements !== []) {
            $this->emitChained($namespace, $class, $plan);
        }

        $this->emitFactory($namespace, $class, $plan);
    }

    private function emitOutput(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(OutputStructure::class);

        $class->setReadOnly();
        $class->setExtends(OutputStructure::class);

        if ($plan->structureType !== null) {
            $this->emitStructureType($namespace, $class, $plan->structureType);
        }

        foreach ($plan->implements as $root) {
            $class->addImplement(
                Imports::importFullName(
                    $namespace,
                    Locations::EXTENSION_NAMESPACE,
                    ExtensionEmitter::createName($root),
                ),
            );
        }

        if ($plan->implements !== []) {
            $this->emitChained($namespace, $class, $plan);
        }

        $this->properties->emit(
            $namespace,
            $class,
            $this->properties->sortRequiredFirst($plan->properties),
            $plan->chains,
            true,
        );
        $this->emitFactory($namespace, $class, $plan);

        if ($this->hasChain($plan)) {
            $this->emitFinder($namespace, $class, $plan);
        }
    }

    /**
     * Makes the structure one that a command can fill in a chain.
     */
    private function emitChained(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(Allocator::class);
        $namespace->addUse(CData::class);

        $class->addImplement(Imports::importFullName($namespace, Locations::EXTENSION_NAMESPACE, 'Chained'));

        $method = $class->addMethod('createNative')
            ->setPublic()
            ->setStatic()
            ->setReturnType(CData::class)
            ->setBody(
                '$data = $memory->new(' . \var_export($plan->struct->name, true) . ");\n"
                . "\$data->sType = self::STRUCTURE_TYPE;\n\n"
                . 'return $data;',
            );

        $method->addParameter('memory')->setType(Allocator::class);
    }

    private function hasChain(StructPlan $plan): bool
    {
        foreach ($plan->properties as $property) {
            if ($property->kind === PropertyKind::CHAIN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds the method that finds the structure of the chain by its class.
     */
    private function emitFinder(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $marker = Imports::import(
            $namespace,
            Locations::EXTENSION_NAMESPACE,
            ExtensionEmitter::createName($plan->chains[0] ?? ''),
        );

        $this->meta->recordFinder(Locations::createClassName(Locations::STRUCT_NAMESPACE, $plan->struct->name));

        $method = $class->addMethod('findChained')
            ->setPublic()
            ->setReturnType('object')
            ->setReturnNullable()
            ->setBody(
                "foreach (\$this->chain as \$item) {\n"
                . "    if (\$item instanceof \$class) {\n"
                . "        return \$item;\n"
                . "    }\n"
                . "}\n\n"
                . 'return null;',
            );

        $method->addParameter('class')->setType('string');
        $method->addComment('Returns the structure of the chain that is an instance of the class.');
        $method->addComment('');
        $method->addComment('@template TChained of ' . $marker);
        $method->addComment('');
        $method->addComment('@param class-string<TChained> $class');
        $method->addComment('');
        $method->addComment('@return TChained|null');
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
            static fn(PropertyPlan $property): bool => $property->kind !== PropertyKind::NEXT
                && $property->kind !== PropertyKind::CHAIN,
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

            if ($property->kind === PropertyKind::NATIVE && $property->name !== $property->field->name) {
                $arguments[] = '// @phpstan-ignore-next-line the member is named differently from the property';
            }

            $isContextual = \in_array($property->field->type, $this->contextual, true);

            $arguments[] = $property->name . ': '
                . $this->reader->createExpression($namespace, $property, $isContextual) . ',';
        }

        if ($this->hasChain($plan) && $plan->role === StructRole::OUTPUT) {
            $arguments[] = 'chain: $chain,';
        }

        $body = "return new self(\n    " . \implode("\n    ", $arguments) . "\n);";

        if ($arguments === []) {
            $body = 'return new self();';
        }

        if ($plan->needsContext) {
            $namespace->addUse(VulkanBase::class);
            $namespace->addUse(Handle::class);
            $namespace->addUse(InvalidStructureException::class);

            $body = "if (\$vk === null) {\n"
                . '    throw InvalidStructureException::becauseContextIsMissing(self::class);'
                . "\n}\n\n" . $body;
        }

        $method = $class->addMethod('createFromCData')
            ->setPublic()
            ->setStatic()
            ->setReturnType('self')
            ->setBody($body);

        $method->addParameter('data')->setType(CData::class);

        if ($this->hasChain($plan) && $plan->role === StructRole::OUTPUT) {
            $method->addParameter('chain', [])->setType('array');
        }

        if ($plan->needsContext) {
            $method->addParameter('vk', null)->setType(VulkanBase::class)->setNullable();
            $method->addParameter('parent', null)->setType(Handle::class)->setNullable();
        }

        $shape = ['@param CData&object{'];

        foreach ($this->findReadProperties($plan) as $property) {
            $shape[] = '    ' . $property->field->name . ': ' . $this->reader->createType($namespace, $property) . ',';
        }

        $shape[] = '} $data';
        $shape[] = '@phpstan-param CData $data';

        if ($this->hasChain($plan) && $plan->role === StructRole::OUTPUT) {
            $shape[] = '@param list<' . Imports::import(
                $namespace,
                Locations::EXTENSION_NAMESPACE,
                ExtensionEmitter::createName($plan->chains[0] ?? ''),
            ) . '> $chain';
        }

        foreach ($shape as $line) {
            $method->addComment($line);
        }
    }

    /**
     * Adds the constant with the value of `sType`, the case of the enum.
     *
     * @throws InvalidRegistryException in case of the structure type is not
     *         defined
     */
    private function emitStructureType(PhpNamespace $namespace, ClassType $class, string $name): void
    {
        $this->getStructureType($name);

        $constant = $class->addConstant(
            'STRUCTURE_TYPE',
            new Literal(Imports::import($namespace, Locations::ENUM_NAMESPACE, 'VkStructureType') . '::' . $name . '->value'),
        );

        $constant->setPublic();
        $constant->setType('int');
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
