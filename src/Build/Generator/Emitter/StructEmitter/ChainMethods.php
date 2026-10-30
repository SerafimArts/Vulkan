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
        if ($plan->version === null) {
            return;
        }

        $class->addImplement(
            Imports::importFullName($namespace, Locations::EXTENSION_NAMESPACE, 'VersionedStructureInterface'),
        );

        $namespace->addUseFunction(Locations::ROOT_NAMESPACE . '\vk_make_api_version');

        $version = $class->addMethod('getVersion')
            ->setPublic()
            ->setStatic()
            ->setReturnType('int')
            ->setBody('return vk_make_api_version(0, ' . $plan->version[0] . ', ' . $plan->version[1] . ', 0);');

        InternalMethod::mark($version);

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
     * Makes the structure one that keeps the structures a command has filled
     * in its chain.
     */
    public function emitRoot(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $marker = Imports::import(
            $namespace,
            Locations::EXTENSION_NAMESPACE,
            ExtensionEmitter::createName($plan->chains[0] ?? ''),
        );

        $class->addImplement(Locations::createClassName(Locations::STRUCT_NAMESPACE, 'ChainRootInterface'));
        $class->addComment('');
        $class->addComment('@implements ChainRootInterface<' . $marker . '>');
    }
}
