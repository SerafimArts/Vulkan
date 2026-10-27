<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandKind;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\CommandResult;
use Serafim\Vulkan\Enum\VkResult;

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
     * Whether the command returns a value together with its success code: the
     * code is `$status` after the call.
     */
    public function hasStatus(CommandPlan $plan): bool
    {
        $kinds = [CommandKind::QUERY_SCALAR, CommandKind::QUERY_POINTER, CommandKind::QUERY_BINARY];

        return $plan->returnsResult
            && \count($plan->command->successCodes) > 1
            && \in_array($plan->kind, $kinds, true);
    }

    /**
     * Sets the type of the value that the method returns.
     *
     * @param string $type the PHP type of the value
     * @param string $doc the type of the value for the docblock
     */
    public function setReturnType(
        PhpNamespace $namespace,
        Method $method,
        CommandPlan $plan,
        string $type,
        string $doc,
    ): void {
        if (!$this->hasStatus($plan)) {
            $method->setReturnType($type);

            return;
        }

        $namespace->addUse(CommandResult::class);
        $namespace->addUse(VkResult::class);

        $method->setReturnType(CommandResult::class);
        $method->addComment('');
        $method->addComment('@return CommandResult<' . $doc . '>');
    }

    /**
     * Returns the statement that returns the value of the command.
     */
    public function createReturnLine(CommandPlan $plan, string $value): string
    {
        if ($this->hasStatus($plan)) {
            return 'return new CommandResult($status, ' . $value . ');';
        }

        return 'return ' . $value . ';';
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

        if ($this->hasStatus($plan)) {
            return [
                ...$lines,
                '$result = ' . $call . ';',
                '\assert(\is_int($result));',
                '',
                '/** @var VkResult::' . \implode('|VkResult::', $plan->command->successCodes) . ' $status */',
                '$status = $this->getResult(' . \var_export($plan->command->name, true) . ', $result);',
            ];
        }

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
