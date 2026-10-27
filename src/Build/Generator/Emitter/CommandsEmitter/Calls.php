<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;

/**
 * The code that calls the native command: the preparation of the arguments,
 * the arguments and the call itself.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Calls
{
    private Preparation $preparation;

    private Arguments $arguments;

    public function __construct()
    {
        $this->preparation = new Preparation();
        $this->arguments = new Arguments();
    }

    /**
     * Writes the arguments of the user to native memory.
     *
     * @return list<string>
     */
    public function createPreparationLines(PhpNamespace $namespace, CommandPlan $plan): array
    {
        return $this->preparation->createLines($namespace, $plan);
    }

    /**
     * @return list<string> the arguments of the native command
     */
    public function createArguments(CommandPlan $plan): array
    {
        return $this->arguments->create($plan);
    }

    /**
     * Returns the arguments except the length and the elements of an output
     * array, which are the last two.
     *
     * @return list<string>
     */
    public function createLeadingArguments(CommandPlan $plan): array
    {
        return $this->arguments->createLeading($plan);
    }

    /**
     * @param list<string> $arguments
     *
     * @return list<string>
     */
    public function createCallLines(CommandPlan $plan, array $arguments): array
    {
        $call = '$procedure(' . \implode(', ', $arguments) . ')';
        $lines = [$this->createProcedureLine($plan), ''];

        if ($plan->returnsResult) {
            $lines[] = '$this->assertSuccess(' . \var_export($plan->command->name, true) . ', ' . $call . ');';

            return $lines;
        }

        $lines[] = $call . ';';

        return $lines;
    }

    public function createProcedureLine(CommandPlan $plan): string
    {
        $parent = $this->findParent($plan);
        $name = \var_export($plan->command->name, true);

        if ($parent === null) {
            return '$procedure = $this->vkGetInstanceProc(' . $name . ');';
        }

        return '$procedure = $this->vkGetInstanceProc(' . $name . ', $' . $parent->name . ');';
    }

    /**
     * Returns the first handle of the command: the handles that are created
     * by it belong to it.
     */
    public function findParent(CommandPlan $plan): ?ParameterPlan
    {
        foreach ($plan->parameters as $parameter) {
            if ($parameter->role === ParameterRole::HANDLE) {
                return $parameter;
            }
        }

        return null;
    }

    public function findHandleParameter(CommandPlan $plan, string $type): ?ParameterPlan
    {
        foreach ($plan->parameters as $parameter) {
            if ($parameter->role === ParameterRole::HANDLE && $parameter->field->type === $type) {
                return $parameter;
            }
        }

        return null;
    }

    public function createParentExpression(?ParameterPlan $parent): string
    {
        if ($parent === null) {
            return 'null';
        }

        return '$' . $parent->name;
    }
}
