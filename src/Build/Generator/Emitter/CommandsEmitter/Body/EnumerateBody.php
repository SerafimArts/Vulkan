<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * A command that fills an array of unknown length.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerateBody implements Body
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

        if ($output->role === ParameterRole::OUTPUT_BINARY) {
            return $this->emitBinary($namespace, $method, $plan);
        }

        if ($output->kind !== TypeKind::HANDLE && $output->kind !== TypeKind::STRUCTURE) {
            return $this->emitNumbers($namespace, $method, $plan, $output);
        }

        $space = match ($output->kind) {
            TypeKind::HANDLE => Locations::HANDLE_NAMESPACE,
            default => Locations::STRUCT_NAMESPACE,
        };
        $class = Imports::import($namespace, $space, $output->field->type);

        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\VkEnumerator');
        $method->setReturnType(Locations::ROOT_NAMESPACE . '\Internal\VkEnumerator');
        $method->addComment('');
        $method->addComment('@return VkEnumerator<' . $class . '>');

        $factory = $class . '::createFromCData($item)';

        if ($output->kind === TypeKind::HANDLE) {
            $parent = $this->calls->findParent($plan);

            $factory = 'new ' . $class . '($this, $item, ' . $this->calls->createParentExpression($parent) . ')';
        }

        $lines = [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            'return VkEnumerator::create(',
            '    $this,',
            '    ' . \var_export($plan->command->name, true) . ',',
            ...$this->createCall($plan),
            '    ' . \var_export($output->field->type, true) . ',',
            '    fn(CData $item): ' . $class . ' => ' . $factory . ',',
        ];

        if ($this->registry->isTypedStructure($output->field->type)) {
            $lines[] = '    ' . $class . '::STRUCTURE_TYPE,';
        }

        $lines[] = ');';

        return $lines;
    }

    /**
     * Returns the lines of the closure that calls the command with the length
     * and the array the enumerator gives it.
     *
     * @return list<string>
     */
    private function createCall(CommandPlan $plan): array
    {
        $lines = ['    static fn(CData $count, ?CData $items): mixed => $procedure('];

        foreach ([...$this->calls->createLeadingArguments($plan), '$count', '$items'] as $argument) {
            $lines[] = '        ' . $argument . ',';
        }

        $lines[] = '    ),';

        return $lines;
    }

    /**
     * The bytes are returned at once as a string.
     *
     * @return list<string>
     */
    private function emitBinary(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\VkEnumerator');

        $method->setReturnType('string');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            'return VkEnumerator::createBinary(',
            '    $this,',
            '    ' . \var_export($plan->command->name, true) . ',',
            ...$this->createCall($plan),
            ');',
        ];
    }

    /**
     * The numbers and the enumerations are returned at once as a list.
     *
     * @return list<string>
     */
    private function emitNumbers(PhpNamespace $namespace, Method $method, CommandPlan $plan, ParameterPlan $output): array
    {
        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\VkEnumerator');

        $type = 'int';
        $factory = 'fn(int $item): int => $item';

        if ($output->kind === TypeKind::FLOAT) {
            $type = 'float';
            $factory = 'fn(float $item): float => $item';
        }

        if ($output->kind === TypeKind::ENUMERATION) {
            $type = Imports::import($namespace, Locations::ENUM_NAMESPACE, $output->field->type);
            $factory = 'fn(int $item): ' . $type . ' => ' . $type . '::from($item)';
        }

        $method->setReturnType('array');
        $method->addComment('');
        $method->addComment('@return list<' . $type . '>');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            'return VkEnumerator::createList(',
            '    $this,',
            '    ' . \var_export($plan->command->name, true) . ',',
            ...$this->createCall($plan),
            '    ' . \var_export($output->field->type, true) . ',',
            '    ' . $factory . ',',
            ');',
        ];
    }
}
