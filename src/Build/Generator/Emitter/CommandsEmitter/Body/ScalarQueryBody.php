<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * A command that writes a number or an enum to a variable.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ScalarQueryBody implements Body
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
        $lines = $this->calls->createPreparationLines($namespace, $plan);

        $lines[] = $native . ' = $this->memory->new(' . \var_export($output->field->type, true) . ');';
        $lines[] = '';
        $lines = [...$lines, ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan))];
        $lines[] = '';

        if ($output->kind === TypeKind::BOOLEAN) {
            $this->calls->setReturnType($namespace, $method, $plan, 'bool', 'bool');
            $lines[] = '$value = ' . $native . '->cdata;';
            $lines[] = '\assert(\is_int($value));';
            $lines[] = '';
            $lines[] = $this->calls->createReturnLine($plan, '$value !== 0');

            return $lines;
        }

        $type = 'int';
        $check = 'is_int';

        if ($output->kind === TypeKind::FLOAT) {
            $type = 'float';
            $check = 'is_float';
        }

        $lines[] = '$value = ' . $native . '->cdata;';
        $lines[] = '\assert(\\' . $check . '($value));';
        $lines[] = '';

        if ($output->kind === TypeKind::ENUMERATION) {
            $class = Imports::import($namespace, Locations::ENUM_NAMESPACE, $output->field->type);

            $this->calls->setReturnType(
                $namespace,
                $method,
                $plan,
                Locations::createClassName(Locations::ENUM_NAMESPACE, $class),
                $class,
            );
            $lines[] = $this->calls->createReturnLine($plan, $class . '::from($value)');

            return $lines;
        }

        $this->calls->setReturnType($namespace, $method, $plan, $type, $type);
        $lines[] = $this->calls->createReturnLine($plan, '$value');

        return $lines;
    }
}
