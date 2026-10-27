<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use FFI\CData;
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
use Serafim\Vulkan\Internal\EnumCase;

/**
 * A command that fills an array of structures or numbers, the number of them
 * is known beforehand.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueryArrayBody implements Body
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

        $result = $this->describe($namespace, $plan, $output, $plan->outputLength ?? '0');

        $method->setReturnType($result->type);

        if ($result->doc !== null) {
            $method->addComment('');
            $method->addComment('@return ' . $result->doc);
        }

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            ...$result->allocate,
            '',
            ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
            '',
            ...$result->read,
            '',
            'return ' . $result->variable . ';',
        ];
    }

    /**
     * Describes how the output array is allocated before the call and read
     * after it.
     */
    public function describe(
        PhpNamespace $namespace,
        CommandPlan $plan,
        ParameterPlan $output,
        string $length,
    ): QueryArray {
        $name = $output->name;
        $native = '$native' . \ucfirst($name);
        $variable = '$' . $name;
        $type = \var_export($output->field->type . '[', true);

        if ($output->role === ParameterRole::OUTPUT_BINARY) {
            return new QueryArray(
                [$native . ' = $this->memory->new(\'uint8_t[\' . \max(1, ' . $length . ') . \']\');'],
                [$variable . ' = \FFI::string(' . $native . ', ' . $length . ');'],
                $variable,
                'string',
            );
        }

        $namespace->addUse(CData::class);

        $total = '$' . $name . 'Total';
        $allocate = [
            $total . ' = ' . $length . ';',
            $native . ' = $this->memory->new(' . $type . ' . \max(1, ' . $total . ') . \']\');',
        ];

        if ($this->registry->isTypedStructure($output->field->type)) {
            $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $output->field->type);

            \array_push(
                $allocate,
                'for ($index = 0; $index < ' . $total . '; ++$index) {',
                '    /** @var CData $element */',
                '    $element = ' . $native . '[$index];',
                '',
                '    $element->sType = ' . $class . '::STRUCTURE_TYPE;',
                '}',
            );
        }

        [$reads, $value, $doc] = $this->createElement($namespace, $plan, $output);

        return new QueryArray(
            $allocate,
            [
                $variable . ' = [];',
                'for ($index = 0; $index < ' . $total . '; ++$index) {',
                ...\array_map(
                    static fn(string $line): string => '    ' . \str_replace('{native}', $native, $line),
                    $reads,
                ),
                '    ' . $variable . '[] = ' . $value . ';',
                '}',
            ],
            $variable,
            'array',
            'list<' . $doc . '>',
        );
    }

    /**
     * Returns the lines that read an element, the expression that makes the
     * value of it and its type.
     *
     * @return array{list<string>, string, string}
     */
    private function createElement(PhpNamespace $namespace, CommandPlan $plan, ParameterPlan $output): array
    {
        $type = $output->field->type;

        if ($output->kind === TypeKind::ENUMERATION) {
            $namespace->addUse(EnumCase::class);
        }

        return match ($output->kind) {
            TypeKind::STRUCTURE => $this->createStructure($namespace, $plan, $type),
            TypeKind::ENUMERATION => [
                ['/** @var int $value */', '$value = {native}[$index];'],
                'EnumCase::createFromValue(' . Imports::import($namespace, Locations::ENUM_NAMESPACE, $type) . '::class, $value)',
                $type,
            ],
            TypeKind::FLOAT => [['/** @var float $value */', '$value = {native}[$index];'], '$value', 'float'],
            TypeKind::BOOLEAN => [['/** @var int $value */', '$value = {native}[$index];'], '$value !== 0', 'bool'],
            default => [['/** @var int $value */', '$value = {native}[$index];'], '$value', 'int'],
        };
    }

    /**
     * @return array{list<string>, string, string}
     */
    private function createStructure(PhpNamespace $namespace, CommandPlan $plan, string $type): array
    {
        $class = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $type);
        $context = '';

        if ($this->structures->needsContext($type)) {
            $context = ', vk: $this, parent: '
                . $this->calls->createParentExpression($this->calls->findParent($plan));
        }

        return [
            ['/** @var CData $element */', '$element = {native}[$index];'],
            $class . '::createFromCData($element' . $context . ')',
            $class,
        ];
    }
}
