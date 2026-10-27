<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * A command that fills a structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueryBody implements Body
{
    public function __construct(
        private Calls $calls,
        private Registry $registry,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null) {
            return [];
        }

        $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $output->field->type);
        $native = 'native' . \ucfirst($output->name);

        $method->setReturnType(Locations::createClassName(Locations::STRUCT_NAMESPACE, $class));

        $lines = $this->calls->createPreparationLines($namespace, $plan);

        $lines[] = '$' . $native . ' = $this->memory->new(' . \var_export($output->field->type, true) . ');';
        if ($this->registry->isTypedStructure($output->field->type)) {
            $lines[] = '$' . $native . '->sType = ' . $class . '::STRUCTURE_TYPE;';
        }

        $lines[] = '';
        $lines = [...$lines, ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan))];
        $lines[] = '';
        $lines[] = 'return ' . $class . '::createFromCData($' . $native . ');';

        return $lines;
    }
}
