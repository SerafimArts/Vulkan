<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Structures;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * A command that fills a structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueryBody implements Body
{
    public function __construct(
        private Calls $calls,
        private Registry $registry,
        private Structures $structures = new Structures(),
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $output = $plan->output;

        if ($output === null) {
            return [];
        }

        $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $output->field->type);
        $native = 'native' . \ucfirst($output->name);

        $method->setReturnType(Locations::createClassName(Locations::STRUCT_NAMESPACE, $class));

        $lines = $this->calls->createPreparationLines($namespace, $plan);

        $lines[] = '$' . $native . ' = $this->memory->new(' . \var_export($output->field->type, true) . ');';
        if ($this->registry->isTypedStructure($output->field->type)) {
            $lines[] = '$' . $native . '->sType = ' . $class . '::STRUCTURE_TYPE;';
        }

        if ($this->structures->isRoot($output->field->type)) {
            return $this->emitChained($namespace, $method, $plan, $class, $lines);
        }

        $buffered = [];

        if ($this->structures->hasArrays($output->field->type)) {
            $buffered[] = $class . '::allocateBuffers($this->memory, $' . $native . ')';
        }

        $lines[] = '';
        $lines = [
            ...$lines,
            ...$this->calls->createBufferedCallLines($plan, $this->calls->createArguments($plan), $buffered),
        ];
        $lines[] = '';
        $lines[] = 'return ' . $class . '::createFromCData($' . $native . $this->createContext($plan, $output) . ');';

        return $lines;
    }

    /**
     * Returns the arguments that the structure which holds handles needs.
     */
    private function createContext(CommandPlan $plan, ParameterPlan $output): string
    {
        if (!$this->structures->needsContext($output->field->type)) {
            return '';
        }

        return ', vk: $this, parent: ' . $this->calls->createParentExpression($this->calls->findParent($plan));
    }

    /**
     * A structure that others are chained to: the user lists the classes of the
     * structures to fill.
     *
     * @param list<string> $lines the lines that prepare the call
     *
     * @return list<string>
     */
    private function emitChained(
        PhpNamespace $namespace,
        Method $method,
        CommandPlan $plan,
        string $class,
        array $lines,
    ): array {
        $output = $plan->output;

        if ($output === null) {
            return $lines;
        }

        $native = '$native' . \ucfirst($output->name);
        $marker = Imports::import(
            $namespace,
            Locations::EXTENSION_NAMESPACE,
            ExtensionEmitter::createName($output->field->type),
        );

        $method->addParameter('chain', [])->setType('array');
        $method->addComment('');
        $method->addComment('@param list<class-string<ChainedStructureInterface&' . $marker . '>> $chain the structures to fill');

        $namespace->addUse(Locations::EXTENSION_NAMESPACE . '\ChainedStructureInterface');
        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\Chain');

        $lines[] = '$natives = Chain::allocate($this->memory, ' . $native . ', $chain, ' . $marker . '::class);';
        $lines[] = '';
        $buffered = [];

        if ($this->structures->hasBuffers($output->field->type)) {
            $buffered[] = '$natives->allocateBuffers($this->memory)';
        }

        $lines = [
            ...$lines,
            ...$this->calls->createBufferedCallLines($plan, $this->calls->createArguments($plan), $buffered),
        ];
        $lines[] = '';
        $lines[] = 'return ' . $class . '::createFromCData(' . $native . ', $natives->read());';

        return $lines;
    }
}
