<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Emitter\MetaRecorder;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * The methods of a structure that takes part in a `pNext` chain: the one
 * that a command fills in the chain of a returned structure, and the one that
 * finds it in the chain.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ChainMethods
{
    public function __construct(
        private MetaRecorder $meta,
    ) {}

    /**
     * Whether the structure keeps the structures a command has filled in its
     * chain.
     */
    public function hasChain(StructPlan $plan): bool
    {
        foreach ($plan->properties as $property) {
            if ($property->kind === PropertyKind::CHAIN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Makes the structure one that a command can fill in a chain.
     */
    public function emitNative(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(Allocator::class);
        $namespace->addUse(CData::class);

        if ($plan->ancestors === []) {
            $class->addImplement(
                Imports::importFullName($namespace, Locations::EXTENSION_NAMESPACE, 'ChainedStructureInterface'),
            );
        }

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

        InternalMethod::mark($method);
    }

    /**
     * Makes the structure of a version of Vulkan tell the structures of the
     * previous versions, whose native structures a command fills together
     * with its own.
     */
    public function emitAncestors(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        if ($plan->ancestors === []) {
            return;
        }

        $class->addImplement(
            Imports::importFullName($namespace, Locations::EXTENSION_NAMESPACE, 'VersionedStructureInterface'),
        );

        $classes = [];

        foreach ($plan->ancestors as $ancestor) {
            $classes[] = $ancestor . '::class';
        }

        $method = $class->addMethod('getAncestors')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->setBody('return [' . \implode(', ', $classes) . '];');

        InternalMethod::mark($method);
    }

    /**
     * Adds the method that finds the structure of the chain by its class.
     */
    public function emitFinder(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
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
}
