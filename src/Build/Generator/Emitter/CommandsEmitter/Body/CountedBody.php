<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use FFI\CData;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Emitter\Code;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Internal\DoubleCall;

/**
 * A command that fills the lengths of the arrays of a structure with the first
 * call: the arrays are allocated for them and the structure is filled with the
 * next one.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CountedBody implements Body
{
    public function __construct(
        private Calls $calls,
        private Registry $registry,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $struct = $plan->output;
        $counts = $plan->outputs[0] ?? null;

        if ($struct === null || $counts === null) {
            return [];
        }

        $namespace->addUse(DoubleCall::class);
        $namespace->addUse(CData::class);

        $structClass = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $struct->field->type);
        $countsClass = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $counts->field->type);
        $nativeStruct = '$native' . \ucfirst($struct->name);
        $nativeCounts = '$native' . \ucfirst($counts->name);
        $name = \var_export($plan->command->name, true);

        $method->setReturnType(Locations::createClassName(Locations::STRUCT_NAMESPACE, $struct->field->type));

        $arguments = $this->createArguments($plan, $struct, $nativeStruct);
        $captured = $this->findCaptured($arguments, $nativeStruct);

        $call = Code::block('function () use (' . \implode(', ', $captured) . ', &' . $nativeStruct . '): mixed {', [
            ...Code::call('return $procedure', $arguments),
        ], '},');

        $allocate = Code::block('function () use (' . $nativeCounts . ', &' . $nativeStruct . '): bool {', [
            ...Code::block('if (' . $nativeStruct . ' !== null) {', ['return false;']),
            '',
            ...$this->createNew($nativeStruct, $struct, $structClass),
            $structClass . '::allocateBuffers($this->memory, ' . $nativeStruct . ', ' . $nativeCounts . ');',
            '',
            'return true;',
        ], '},');

        $reset = Code::block('static function () use (&' . $nativeStruct . '): void {', [$nativeStruct . ' = null;'], '},');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            ...$this->createNew($nativeCounts, $counts, $countsClass),
            $nativeStruct . ' = null;',
            '',
            ...Code::call('DoubleCall::run', ['$this,', $name . ',', ...$call, ...$allocate, ...$reset]),
            '',
            '/** @var CData|null $allocated */',
            '$allocated = ' . $nativeStruct . ';',
            '',
            '\assert($allocated !== null);',
            '',
            'return ' . $structClass . '::createFromCData($allocated, ' . $nativeCounts . ');',
        ];
    }

    /**
     * Returns the lines that allocate the native structure and set its `sType`.
     *
     * @return list<string>
     */
    private function createNew(string $native, ParameterPlan $parameter, string $class): array
    {
        $lines = [$native . ' = $this->memory->new(' . \var_export($parameter->field->type, true) . ');'];

        if ($this->registry->isTypedStructure($parameter->field->type)) {
            $lines[] = $native . '->sType = ' . $class . '::STRUCTURE_TYPE;';
        }

        return $lines;
    }

    /**
     * Returns the arguments of the command: the structure is passed when it is
     * allocated.
     *
     * @return list<string>
     */
    private function createArguments(CommandPlan $plan, ParameterPlan $struct, string $native): array
    {
        $arguments = [];

        foreach ($this->calls->createArguments($plan) as $index => $argument) {
            if ($plan->parameters[$index]->name === $struct->name) {
                $argument = '$this->addr(' . $native . ')';
            }

            $arguments[] = $argument . ',';
        }

        return $arguments;
    }

    /**
     * Returns the variables of the arguments that the closure of the call uses,
     * with the procedure, the structure the closure changes is not one of them.
     *
     * @param list<string> $arguments
     *
     * @return list<string>
     */
    private function findCaptured(array $arguments, string $native): array
    {
        $variables = ['$procedure' => '$procedure'];

        foreach ($arguments as $argument) {
            \preg_match_all('/\$(\w+)/', $argument, $matches);

            foreach ($matches[1] as $variable) {
                if ($variable !== 'this' && '$' . $variable !== $native) {
                    $variables['$' . $variable] = '$' . $variable;
                }
            }
        }

        return \array_values($variables);
    }
}
