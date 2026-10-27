<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
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
        /**
         * The returned structures that others are chained to
         *
         * @var list<non-empty-string>
         */
        private array $roots = [],
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

        if (\in_array($output->field->type, $this->roots, true)) {
            return $this->emitChained($namespace, $method, $plan, $class, $lines);
        }

        $lines[] = '';
        $lines = [...$lines, ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan))];
        $lines[] = '';
        $lines[] = 'return ' . $class . '::createFromCData($' . $native . ');';

        return $lines;
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
        $method->addComment('@param list<class-string<Chained&' . $marker . '>> $chain the structures to fill');

        $namespace->addUse(Locations::EXTENSION_NAMESPACE . '\Chained');
        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\Chain');

        $lines[] = '$natives = Chain::allocate($this->memory, ' . $native . ', $chain, ' . $marker . '::class);';
        $lines[] = '';
        $lines = [...$lines, ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan))];
        $lines[] = '';
        $lines[] = 'return ' . $class . '::createFromCData(' . $native . ', $natives->read());';

        return $lines;
    }
}
