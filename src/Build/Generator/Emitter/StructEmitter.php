<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\BufferMethod;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\ChainMethods;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\Codecs;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\NumberTypes;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\FactoryMethod;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Properties;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Reader;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Shape;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\WriteMethod;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Writer;
use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Internal\Memory\Allocation;
use Serafim\Vulkan\Struct\CountedStructureInterface;
use Serafim\Vulkan\Struct\InputStructureInterface;
use Serafim\Vulkan\Struct\InputStructureTrait;
use Serafim\Vulkan\Struct\OutputStructureInterface;
use Serafim\Vulkan\Struct\OutputStructureTrait;

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

    private WriteMethod $write;

    private FactoryMethod $factory;

    private ChainMethods $chains;

    private BufferMethod $buffers;

    private SpecificationTags $tags;

    /**
     * @param list<non-empty-string> $contextual the returned structures that
     *        hold handles
     * @param list<non-empty-string> $buffered the returned structures whose
     *        arrays Vulkan fills
     */
    public function __construct(
        private Registry $registry,
        TypeResolver $types,
        array $contextual = [],
        MetaRecorder $meta = new MetaRecorder(),
        array $buffered = [],
    ) {
        $numbers = new NumberTypes($types);
        $codecs = new Codecs($types, $numbers);
        $reader = new Reader($types, $codecs);
        $shape = new Shape($reader, $registry);

        $this->files = new PhpFiles();
        $this->properties = new Properties($types, $codecs, $meta);
        $this->chains = new ChainMethods($meta);
        $this->write = new WriteMethod(new Writer($codecs), $shape);
        $this->factory = new FactoryMethod($numbers, $reader, $shape, $this->chains, $contextual);
        $this->buffers = new BufferMethod($registry, $shape, $buffered);
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

        if (!$plan->hasDescendants) {
            $class->setFinal();
        }

        if ($plan->ancestors !== []) {
            $class->setExtends(Locations::createClassName(Locations::STRUCT_NAMESPACE, $plan->ancestors[0]));
        }

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
        $namespace->addUse(InputStructureInterface::class);
        $namespace->addUse(InputStructureTrait::class);
        $namespace->addUse(CData::class);
        $namespace->addUse(Allocation::class);

        if ($plan->ancestors === []) {
            $class->addImplement(InputStructureInterface::class);
            $class->addTrait(InputStructureTrait::class);
        }

        $native = $class->addConstant('NATIVE_TYPE', $plan->struct->name);

        $native->setProtected();
        $native->setType('string');

        $this->properties->emit(
            $namespace,
            $class,
            $this->properties->sortRequiredFirst($plan->properties),
            $plan->chains,
            inherited: $plan->inherited,
        );

        $this->emitMarkers($namespace, $class, $plan);

        if ($plan->structureType !== null) {
            $this->emitStructureType($namespace, $class, $plan->structureType);
        }

        $this->write->emit($namespace, $class, $plan);
    }

    /**
     * @throws InvalidRegistryException
     */
    private function emitDual(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $this->emitInput($namespace, $class, $plan);

        if ($plan->implements !== []) {
            $this->chains->emitNative($namespace, $class, $plan);
        }

        $this->chains->emitAncestors($namespace, $class, $plan);
        $this->factory->emit($namespace, $class, $plan);
    }

    /**
     * @throws InvalidRegistryException
     */
    private function emitOutput(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(OutputStructureInterface::class);
        $namespace->addUse(OutputStructureTrait::class);

        $class->setReadOnly();

        if ($plan->ancestors === []) {
            $class->addImplement(OutputStructureInterface::class);
            $class->addTrait(OutputStructureTrait::class);
        }

        if ($plan->counts !== null) {
            $namespace->addUse(CountedStructureInterface::class);

            $class->setImplements([CountedStructureInterface::class]);
        }

        if ($plan->structureType !== null) {
            $this->emitStructureType($namespace, $class, $plan->structureType);
        }

        $this->emitMarkers($namespace, $class, $plan);

        if ($plan->implements !== []) {
            $this->chains->emitNative($namespace, $class, $plan);
        }

        $this->chains->emitAncestors($namespace, $class, $plan);

        if ($this->buffers->hasArrays($plan)) {
            $this->buffers->emit($namespace, $class, $plan);
        }

        $this->properties->emit(
            $namespace,
            $class,
            $this->properties->sortRequiredFirst($plan->properties),
            $plan->chains,
            true,
            $plan->inherited,
        );
        $this->factory->emit($namespace, $class, $plan);

        if ($this->chains->hasChain($plan)) {
            $this->chains->emitFinder($namespace, $class, $plan);
        }
    }

    /**
     * Makes the structure one that the `pNext` of the structures it extends
     * accepts.
     */
    private function emitMarkers(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        if ($plan->ancestors !== []) {
            return;
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
