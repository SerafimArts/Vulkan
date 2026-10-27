<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;

/**
 * A command that fills the bytes of the size the user gives.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueryBinaryBody implements Body
{
    public function __construct(
        private Calls $calls,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null || $plan->outputLength === null) {
            return [];
        }

        $native = '$native' . \ucfirst($output->name);

        $method->setReturnType('string');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $native . ' = $this->memory->new(\'uint8_t[\' . \max(1, ' . $plan->outputLength . ') . \']\');',
            '',
            ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
            '',
            'return \FFI::string(' . $native . ', ' . $plan->outputLength . ');',
        ];
    }
}
