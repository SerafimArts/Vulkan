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
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Structures;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * A command that fills several outputs: the method returns them by the names
 * of the parameters.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class MultipleBody implements Body
{
    public function __construct(
        private Calls $calls,
        private Registry $registry,
        private Structures $structures,
        private EnumerateBody $enumerations,
        private QueryArrayBody $arrays,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $lines = $this->calls->createPreparationLines($namespace, $plan);
        $reads = [];
        $values = [];
        $docs = [];
        $enumeration = null;
        $assignment = [];

        foreach ($plan->outputs as $output) {
            if ($plan->isEnumerated($output)) {
                $enumeration = $this->enumerations->describe($namespace, $plan, $output);
                $assignment = $this->createAssignment($output, $enumeration);
                $docs[$output->name] = $enumeration->doc ?? $enumeration->type;
                $values[$output->name] = '$' . $output->name;

                continue;
            }

            $side = $this->describe($namespace, $plan, $output);

            \array_push($lines, ...$side->allocate);
            \array_push($reads, $side->read);

            $docs[$output->name] = $side->doc ?? $side->type;
            $values[$output->name] = $side->variable;
        }

        $method->setReturnType('array');
        $method->addComment('');
        $method->addComment($this->createShape($docs));

        $result = ['return ['];

        foreach ($values as $name => $variable) {
            $result[] = '    ' . \var_export($name, true) . ' => ' . $variable . ',';
        }

        $result[] = '];';

        if ($enumeration === null) {
            return [
                ...$lines,
                '',
                ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
                '',
                ...$this->flatten($reads),
                '',
                ...$result,
            ];
        }

        return [
            ...$lines,
            $this->calls->createProcedureLine($plan),
            ...$enumeration->setup,
            '',
            ...$assignment,
            ...$this->flatten($reads),
            '',
            ...$result,
        ];
    }

    /**
     * @param array<string, string> $docs
     */
    private function createShape(array $docs): string
    {
        $items = [];

        foreach ($docs as $name => $doc) {
            $items[] = $name . ': ' . $doc;
        }

        return '@return array{' . \implode(', ', $items) . '}';
    }

    /**
     * @param list<list<string>> $groups
     *
     * @return list<string>
     */
    private function flatten(array $groups): array
    {
        return \array_merge([], ...$groups);
    }

    /**
     * Returns the lines that assign the value of the enumeration to the
     * variable of the output.
     *
     * @return list<string>
     */
    private function createAssignment(ParameterPlan $output, Enumeration $enumeration): array
    {
        $expression = $enumeration->expression;
        $first = (string) \array_shift($expression);
        $last = (string) \array_pop($expression);

        return ['$' . $output->name . ' = ' . $first, ...$expression, $last . ';'];
    }

    /**
     * Describes an output that is filled by the same call as the others.
     */
    private function describe(PhpNamespace $namespace, CommandPlan $plan, ParameterPlan $output): QueryArray
    {
        if ($output->role === ParameterRole::OUTPUT_ARRAY || $output->role === ParameterRole::OUTPUT_BINARY) {
            return $this->arrays->describe($namespace, $plan, $output, $plan->outputLengths[$output->name] ?? '0');
        }

        $native = '$native' . \ucfirst($output->name);
        $variable = '$' . $output->name;
        $type = $output->field->type;

        if ($output->role === ParameterRole::OUTPUT_STRUCTURE) {
            return $this->describeStructure($namespace, $plan, $output, $native);
        }

        $allocate = [$native . ' = $this->memory->new(' . \var_export($type, true) . ');'];

        return match ($output->kind) {
            TypeKind::FLOAT => new QueryArray(
                $allocate,
                ['/** @var float ' . $variable . ' */', $variable . ' = ' . $native . '->cdata;'],
                $variable,
                'float',
            ),
            TypeKind::BOOLEAN => new QueryArray(
                $allocate,
                ['$' . $output->name . 'Raw = ' . $native . '->cdata;', '\assert(\is_int($' . $output->name . 'Raw));', $variable . ' = $' . $output->name . 'Raw !== 0;'],
                $variable,
                'bool',
            ),
            TypeKind::ENUMERATION => new QueryArray(
                $allocate,
                [
                    '/** @var int $' . $output->name . 'Raw */',
                    '$' . $output->name . 'Raw = ' . $native . '->cdata;',
                    $variable . ' = ' . Imports::import($namespace, Locations::ENUM_NAMESPACE, $type)
                        . '::from($' . $output->name . 'Raw);',
                ],
                $variable,
                Locations::createClassName(Locations::ENUM_NAMESPACE, $type),
                $type,
            ),
            default => new QueryArray(
                $allocate,
                ['/** @var int ' . $variable . ' */', $variable . ' = ' . $native . '->cdata;'],
                $variable,
                'int',
            ),
        };
    }

    private function describeStructure(
        PhpNamespace $namespace,
        CommandPlan $plan,
        ParameterPlan $output,
        string $native,
    ): QueryArray {
        $type = $output->field->type;
        $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $type);
        $variable = '$' . $output->name;
        $allocate = [$native . ' = $this->memory->new(' . \var_export($type, true) . ');'];
        $context = '';

        if ($this->registry->isTypedStructure($type)) {
            $allocate[] = $native . '->sType = ' . $class . '::STRUCTURE_TYPE;';
        }

        if ($this->structures->needsContext($type)) {
            $context = ', vk: $this, parent: '
                . $this->calls->createParentExpression($this->calls->findParent($plan));
        }

        return new QueryArray(
            $allocate,
            [$variable . ' = ' . $class . '::createFromCData(' . $native . $context . ');'],
            $variable,
            Locations::createClassName(Locations::STRUCT_NAMESPACE, $type),
            $class,
        );
    }
}
