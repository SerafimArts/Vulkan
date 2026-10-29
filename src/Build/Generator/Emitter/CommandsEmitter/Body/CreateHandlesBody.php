<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use FFI\CData;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\HandleFactory;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Handle\Handle;

/**
 * A command that creates several handles and reports the number of them in
 * the structure it fills: the first call returns the number, the second one
 * creates the handles.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CreateHandlesBody implements Body
{
    public function __construct(
        private Calls $calls,
        private HandleFactory $handles,
        private Registry $registry,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;
        $member = $plan->handles;

        if ($output === null || $member === null || $member->length === null) {
            return [];
        }

        $class = Imports::import($namespace, Locations::HANDLE_NAMESPACE, $member->type);
        $parent = $this->calls->findParent($plan);
        $native = '$native' . \ucfirst($output->name);
        $count = $native . '->' . $member->length;
        $call = $this->calls->createCallLines($plan, $this->calls->createArguments($plan));

        $namespace->addUse(Handle::class);
        $namespace->addUse(CData::class);
        $method->setReturnType('array');
        $method->addComment('');
        $method->addComment('@return list<' . $class . '>');

        [$prepare, $factory] = $this->handles->create($plan, $output, $class, $parent, '$element', $member->type);

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $native . ' = $this->memory->new(' . \var_export($output->field->type, true) . ');',
            $native . '->sType = ' . $this->createStructureType($namespace, $output->field->type) . ';',
            '',
            ...$call,
            '',
            '/** @var int<0, max> $total */',
            '$total = ' . $count . ';',
            '',
            'if ($total === 0) {',
            '    return [];',
            '}',
            '',
            '$handles = $this->memory->newArray(' . \var_export($member->type, true) . ', $total, ' . $native . ');',
            $native . '->' . $member->name . ' = $handles;',
            '',
            ...\array_slice($call, 2),
            '',
            ...$prepare,
            '$created = [];',
            'for ($index = 0; $index < ' . $count . '; ++$index) {',
            '    /** @var CData $element */',
            '    $element = $handles[$index];',
            '',
            '    $created[] = ' . $factory . ';',
            '}',
            '',
            'return $created;',
        ];
    }

    /**
     * Returns the value of `sType` of the structure.
     */
    private function createStructureType(PhpNamespace $namespace, string $type): string
    {
        $enum = Imports::import($namespace, Locations::ENUM_NAMESPACE, 'VkStructureType');

        foreach ($this->registry->getStruct($type)->members as $member) {
            if ($member->name === 'sType' && $member->value !== null) {
                return $enum . '::' . $member->value . '->value';
            }
        }

        return '0';
    }
}
