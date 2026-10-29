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
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Internal\EnumCase;

/**
 * A command that fills an array of unknown length.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerateBody implements Body
{
    private const string ENUMERATOR = Locations::ROOT_NAMESPACE . '\Internal\VkEnumerator';

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

        $enumeration = $this->describe($namespace, $plan, $output, true);

        $method->setReturnType($enumeration->type);

        if ($enumeration->marker !== null) {
            $namespace->addUse(Locations::EXTENSION_NAMESPACE . '\ChainedStructureInterface');

            $method->addParameter('chain', [])->setType('array');
            $method->addComment('');
            $method->addComment(
                '@param list<class-string<ChainedStructureInterface&' . $enumeration->marker . '>> $chain the structures to fill',
            );
        }

        if ($enumeration->doc !== null) {
            $method->addComment('');
            $method->addComment('@return ' . $enumeration->doc);
        }

        $expression = $enumeration->expression;
        $first = (string) \array_shift($expression);
        $last = (string) \array_pop($expression);

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            ...$enumeration->setup,
            '',
            'return ' . $first,
            ...$expression,
            $last . ';',
        ];
    }

    /**
     * Describes the call that fills the array of the output.
     *
     * @param bool $isChained whether the user lists the structures of the chain
     *        of each element
     */
    public function describe(
        PhpNamespace $namespace,
        CommandPlan $plan,
        ParameterPlan $output,
        bool $isChained = false,
    ): Enumeration {
        $namespace->addUse(self::ENUMERATOR);

        if ($output->role === ParameterRole::OUTPUT_BINARY) {
            return $this->describeBinary($plan);
        }

        if ($output->kind !== TypeKind::HANDLE && $output->kind !== TypeKind::STRUCTURE) {
            return $this->describeNumbers($namespace, $plan, $output);
        }

        $space = Locations::STRUCT_NAMESPACE;

        if ($output->kind === TypeKind::HANDLE) {
            $space = Locations::HANDLE_NAMESPACE;
        }

        $class = Imports::import($namespace, $space, $output->field->type);

        if ($isChained && $this->structures->isRoot($output->field->type)) {
            return $this->describeChained($namespace, $plan, $output, $class);
        }

        $parent = $this->calls->createParentExpression($this->calls->findParent($plan));
        $factory = $class . '::createFromCData($item)';

        if ($this->structures->needsContext($output->field->type)) {
            $factory = $class . '::createFromCData($item, vk: $this, parent: ' . $parent . ')';
        }

        if ($output->kind === TypeKind::HANDLE) {
            $factory = 'new ' . $class . '($this, $item, ' . $parent . ')';
        }

        $expression = [
            'VkEnumerator::create(',
            '    $this,',
            '    ' . \var_export($plan->command->name, true) . ',',
            ...$this->createCall($plan),
            '    ' . \var_export($output->field->type, true) . ',',
            '    fn(CData $item): ' . $class . ' => ' . $factory . ',',
        ];

        if ($this->registry->isTypedStructure($output->field->type)) {
            $expression[] = '    ' . $class . '::STRUCTURE_TYPE,';
        }

        if ($this->structures->hasArrays($output->field->type)) {
            $expression[] = '    buffered: fn(CData $item, CData $items): bool => ' . $class
                . '::allocateBuffers($this->memory, $item, $items),';
        }

        $expression[] = ')';

        return new Enumeration([], $expression, self::ENUMERATOR, 'VkEnumerator<' . $class . '>');
    }

    /**
     * The structures that others are chained to: the user lists the classes of
     * the structures to fill in each element.
     */
    private function describeChained(
        PhpNamespace $namespace,
        CommandPlan $plan,
        ParameterPlan $output,
        string $class,
    ): Enumeration {
        $marker = Imports::import(
            $namespace,
            Locations::EXTENSION_NAMESPACE,
            ExtensionEmitter::createName($output->field->type),
        );

        $namespace->addUse(Locations::ROOT_NAMESPACE . '\Internal\Chain');

        $arguments = $this->calls->createLeadingArguments($plan);
        $call = [
            '    function (CData $count, ?CData $items) use (' . \implode(', ', $this->findCaptured($arguments))
                . ', $chain, &$natives): mixed {',
            '        if ($items !== null) {',
            '            $natives = Chain::allocateEach(',
            '                $this->memory,',
            '                $items,',
            '                $count,',
            '                ' . $class . '::STRUCTURE_TYPE,',
            '                $chain,',
            '                ' . $marker . '::class,',
            '            );',
            '        }',
            '',
            '        return $procedure(',
        ];

        foreach ([...$arguments, '$count', '$items'] as $argument) {
            $call[] = '            ' . $argument . ',';
        }

        $call[] = '        );';
        $call[] = '    },';

        return new Enumeration(
            [
                '/** @var list<Chain<ChainedStructureInterface&' . $marker . '>> $natives */',
                '$natives = [];',
            ],
            [
                'VkEnumerator::create(',
                '    $this,',
                '    ' . \var_export($plan->command->name, true) . ',',
                ...$call,
                '    ' . \var_export($output->field->type, true) . ',',
                '    function (CData $item, int $index) use (&$natives): ' . $class . ' {',
                '        return ' . $class . '::createFromCData($item, $natives[$index]->read());',
                '    },',
                ')',
            ],
            self::ENUMERATOR,
            'VkEnumerator<' . $class . '>',
            $marker,
        );
    }

    /**
     * The bytes are returned at once as a string.
     */
    private function describeBinary(CommandPlan $plan): Enumeration
    {
        return new Enumeration(
            [],
            [
                'VkEnumerator::createBinary(',
                '    $this,',
                '    ' . \var_export($plan->command->name, true) . ',',
                ...$this->createCall($plan),
                ')',
            ],
            'string',
        );
    }

    /**
     * The numbers and the enumerations are returned at once as a list.
     */
    private function describeNumbers(PhpNamespace $namespace, CommandPlan $plan, ParameterPlan $output): Enumeration
    {
        $type = 'int';
        $factory = 'fn(int $item): int => $item';

        if ($output->kind === TypeKind::FLOAT) {
            $type = 'float';
            $factory = 'fn(float $item): float => $item';
        }

        if ($output->kind === TypeKind::ENUMERATION) {
            $namespace->addUse(EnumCase::class);

            $type = Imports::import($namespace, Locations::ENUM_NAMESPACE, $output->field->type);
            $factory = 'fn(int $item): ' . $type . ' => EnumCase::createFromValue('
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $output->field->type)
                . '::class, $item)';
        }

        return new Enumeration(
            [],
            [
                'VkEnumerator::createList(',
                '    $this,',
                '    ' . \var_export($plan->command->name, true) . ',',
                ...$this->createCall($plan),
                '    ' . \var_export($output->field->type, true) . ',',
                '    ' . $factory . ',',
                ')',
            ],
            'array',
            'list<' . $type . '>',
        );
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
     * Returns the variables of the arguments that the closure of the call
     * uses, with the procedure.
     *
     * @param list<string> $arguments
     *
     * @return list<string>
     */
    private function findCaptured(array $arguments): array
    {
        $variables = ['$procedure' => '$procedure'];

        foreach ($arguments as $argument) {
            \preg_match_all('/\$(\w+)/', $argument, $matches);

            foreach ($matches[1] as $name) {
                $variables['$' . $name] = '$' . $name;
            }
        }

        return \array_values($variables);
    }
}
