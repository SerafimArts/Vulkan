<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use FFI\CData;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;

/**
 * A command that writes a pointer to native memory.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class PointerQueryBody implements Body
{
    public function __construct(
        private Calls $calls,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null) {
            return [];
        }

        $native = '$native' . \ucfirst($output->name);

        $namespace->addUse(CData::class);
        $this->calls->setReturnType($namespace, $method, $plan, CData::class, 'CData');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $native . ' = $this->memory->new(\'void*\');',
            '',
            ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
            '',
            $this->calls->createReturnLine($plan, $native),
        ];
    }
}
