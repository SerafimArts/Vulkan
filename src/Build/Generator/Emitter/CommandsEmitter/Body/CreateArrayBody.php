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
use Serafim\Vulkan\Handle\Handle;

/**
 * A command that creates several handles, the number of them is known
 * beforehand.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CreateArrayBody implements Body
{
    public function __construct(
        private Calls $calls,
        private HandleFactory $handles,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null || $plan->outputLength === null) {
            return [];
        }

        $class = Imports::import($namespace, Locations::HANDLE_NAMESPACE, $output->field->type);
        $parent = $this->calls->findParent($plan);
        $native = 'native' . \ucfirst($output->name);

        $namespace->addUse(Handle::class);
        $namespace->addUse(CData::class);
        $method->setReturnType('array');
        $method->addComment('');
        $method->addComment('@return list<' . $class . '>');

        [$prepare, $factory] = $this->handles->create($plan, $output, $class, $parent, '$element');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            '$total = ' . $plan->outputLength . ';',
            '$' . $native . ' = $this->memory->new(' . \var_export($output->field->type . '[', true)
                . ' . \max(1, $total) . \']\');',
            '',
            ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
            '',
            ...$prepare,
            '$created = [];',
            'for ($index = 0; $index < $total; ++$index) {',
            '    /** @var CData $element */',
            '    $element = $' . $native . '[$index];',
            '',
            '    $created[] = ' . $factory . ';',
            '}',
            '',
            'return $created;',
        ];
    }
}
