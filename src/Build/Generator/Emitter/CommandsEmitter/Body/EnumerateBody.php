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
 * A command that fills an array of unknown length.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerateBody implements Body
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

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            'return VkEnumerator::create(',
            '    $this,',
            '    ' . \var_export($plan->command->name, true) . ',',
            '    $procedure,',
            '    [' . \implode(', ', $this->calls->createLeadingArguments($plan)) . '],',
            '    ' . \var_export($output->field->type, true) . ',',
            '    fn(CData $item): ' . $class . ' => ' . $factory . ',',
            ');',
        ];
    }
}
