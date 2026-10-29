<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator;

use Serafim\Vulkan\Build\Generator\Analysis\Planner;
use Serafim\Vulkan\Build\Generator\Analysis\StructRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Structures;
use Serafim\Vulkan\Build\Generator\Emitter\ConstantsEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\EnumEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\HandleEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\MetaEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\MetaRecorder;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;
use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Generates the PHP code of the whole Vulkan API from the registry.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CodeGenerator
{
    /**
     * @throws InvalidRegistryException in case of a definition is missing
     */
    public function generate(Registry $registry): GenerationResult
    {
        $types = new TypeResolver($registry);
        $plan = new Planner($registry, $types)->plan();

        $roots = [];

        foreach ($plan->structs as $struct) {
            if ($struct->role === StructRole::OUTPUT && \in_array($struct->struct->name, $plan->extensions, true)) {
                $roots[] = $struct->struct->name;
            }
        }

        $contextual = [];

        foreach ($plan->structs as $struct) {
            if ($struct->needsContext) {
                $contextual[] = $struct->struct->name;
            }
        }

        $buffered = [];
        $filled = [];

        foreach ($plan->structs as $struct) {
            foreach ($struct->properties as $property) {
                if ($property->kind->isQueried()) {
                    $buffered = [...$buffered, ...$struct->implements];
                    $filled[] = $struct->struct->name;
                }
            }
        }

        $meta = new MetaRecorder();
        $files = [
            ...new CommandsEmitter($registry, $types, new Structures(
                $roots,
                $contextual,
                \array_values(\array_unique($buffered)),
                \array_values(\array_unique($filled)),
            ), $meta)->emit($plan->commands),
            new ConstantsEmitter()->emit($registry->getDefines()),
        ];

        $structs = new StructEmitter($registry, $types, $contextual, $meta, \array_values(\array_unique($filled)));
        $handles = new HandleEmitter($registry);
        $enums = new EnumEmitter($registry);

        foreach ($plan->structs as $struct) {
            $files[] = $structs->emit($struct);
        }

        $extensions = new ExtensionEmitter();

        foreach ($plan->extensions as $extension) {
            $files[] = $extensions->emit($extension);
        }

        foreach ($plan->handles as $handle) {
            $files[] = $handles->emit($handle);
        }

        foreach ($plan->enums as $enum) {
            $files[] = $enums->emit($enum);
        }

        $files[] = new MetaEmitter($registry)->emit($meta);

        return new GenerationResult($files, $plan->skipped, $plan->skippedStructs);
    }
}
