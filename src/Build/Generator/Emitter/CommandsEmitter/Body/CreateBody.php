<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\HandleFactory;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Handle\Handle;

/**
 * A command that creates a handle.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CreateBody implements Body
{
    public function __construct(
        private Calls $calls,
        private HandleFactory $handles,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null) {
            return [];
        }

        $class = Imports::import($namespace, Locations::HANDLE_NAMESPACE, $output->field->type);
        $parent = $this->calls->findParent($plan);

        $method->setReturnType(Locations::createClassName(Locations::HANDLE_NAMESPACE, $class));

        $native = 'native' . \ucfirst($output->name);
        $lines = $this->calls->createPreparationLines($namespace, $plan);

        $lines[] = '$' . $native . ' = $this->memory->new(' . \var_export($output->field->type, true) . ');';
        $lines[] = '';
        $lines = [...$lines, ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan))];
        $lines[] = '';
        $namespace->addUse(Handle::class);

        [$prepare, $factory] = $this->handles->create($plan, $output, $class, $parent, '$' . $native);

        return [...$lines, ...$prepare, 'return ' . $factory . ';'];
    }
}
