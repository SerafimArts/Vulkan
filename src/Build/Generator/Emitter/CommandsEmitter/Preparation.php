<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use FFI\CData;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Exception\InvalidStructure\WrongArrayLengthException;

/**
 * Writes the arguments of the user to native memory before the call.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Preparation
{
    /**
     * @return list<string>
     */
    public function createLines(PhpNamespace $namespace, CommandPlan $plan): array
    {
        $lines = [];

        foreach ($plan->parameters as $parameter) {
            $parameterLines = match ($parameter->role) {
                ParameterRole::INPUT_ARRAY => $this->createArrayLines($namespace, $plan, $parameter),
                ParameterRole::INPUT_STRING, ParameterRole::INPUT_BINARY => $this->createStringLines($parameter),
                ParameterRole::INPUT_STRUCTURE => $this->createStructureLines($parameter),
                ParameterRole::INPUT_NATIVE => $this->createNativeLines($parameter),
                ParameterRole::INPUT_POINTER => $this->createPointerLines($parameter),
                default => [],
            };

            \array_push($lines, ...$parameterLines);
        }

        if ($lines !== []) {
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function createStructureLines(ParameterPlan $parameter): array
    {
        $operator = '->';

        if ($parameter->field->isOptional) {
            $operator = '?->';
        }

        return [
            '$native' . \ucfirst($parameter->name) . ' = $' . $parameter->name . $operator
                . 'toCData($this->memory);',
        ];
    }

    /**
     * Turns a value of the system to the type of the parameter.
     *
     * @return list<string>
     */
    private function createNativeLines(ParameterPlan $parameter): array
    {
        $native = '$native' . \ucfirst($parameter->name);
        $value = '$' . $parameter->name;
        $type = $parameter->field->type . \str_repeat('*', $parameter->field->pointerDepth);
        $cast = $native . ' = $this->ffi->cast(' . \var_export($type, true) . ', ' . $value . ');';

        if (!$parameter->field->isOptional) {
            return [$cast, ''];
        }

        return [$native . ' = null;', 'if (' . $value . ' !== null) {', '    ' . $cast, '}', ''];
    }

    /**
     * Writes a string of the user to native memory.
     *
     * @return list<string>
     */
    private function createStringLines(ParameterPlan $parameter): array
    {
        $native = '$native' . \ucfirst($parameter->name);
        $value = '$' . $parameter->name;

        if (!$parameter->field->isOptional) {
            return [$native . ' = $this->memory->newString(' . $value . ');', ''];
        }

        return [
            $native . ' = null;',
            'if (' . $value . ' !== null) {',
            '    ' . $native . ' = $this->memory->newString(' . $value . ');',
            '}',
            '',
        ];
    }

    /**
     * Writes the elements of an input array to a native array: an array of
     * the fixed size has to have exactly that many, the arrays of arrays are
     * written one by one.
     *
     * @return list<string>
     */
    private function createArrayLines(PhpNamespace $namespace, CommandPlan $plan, ParameterPlan $parameter): array
    {
        $native = '$native' . \ucfirst($parameter->name);
        $value = '$' . $parameter->name;
        $create = $this->createElements($namespace, $parameter, $value);

        if ($parameter->field->pointerDepth === 2) {
            $namespace->addUse(CData::class);

            $create = '$this->memory->toPointerArray(' . \var_export($parameter->field->type, true)
                . ', \array_map(fn(array $items): CData => '
                . $this->createElements($namespace, $parameter, '$items') . ', ' . $value . '))';
        }

        if ($parameter->field->arraySize !== null) {
            $namespace->addUse(WrongArrayLengthException::class);

            return [
                'if (\count(' . $value . ') !== ' . $parameter->field->arraySize . ') {',
                '    throw WrongArrayLengthException::becauseArrayHasWrongLength('
                    . \var_export($plan->command->name, true) . ', '
                    . \var_export($parameter->name, true) . ', ' . $parameter->field->arraySize . ');',
                '}',
                '',
                $native . ' = ' . $create . ';',
                '',
            ];
        }

        return [
            $native . ' = null;',
            'if (' . $value . ' !== []) {',
            '    ' . $native . ' = ' . $create . ';',
            '}',
            '',
        ];
    }

    /**
     * Returns the expression that writes the elements of the list to a native
     * array.
     */
    private function createElements(PhpNamespace $namespace, ParameterPlan $parameter, string $value): string
    {
        $type = \var_export($parameter->field->type, true);

        return match ($parameter->kind) {
            TypeKind::STRUCTURE => Imports::import($namespace, Locations::STRUCT_NAMESPACE, $parameter->field->type)
                . '::createArray($this->memory, ' . $value . ')',
            TypeKind::HANDLE => '$this->memory->toHandleArray(' . $type . ', ' . $value . ')',
            TypeKind::ENUMERATION => '$this->memory->toNumberArray(' . $type . ', \array_map(static fn('
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $parameter->field->type)
                . ' $item): int => $item->value, ' . $value . '))',
            TypeKind::BOOLEAN => '$this->memory->toNumberArray(' . $type
                . ', \array_map(static fn(bool $item): int => (int) $item, ' . $value . '))',
            default => '$this->memory->toNumberArray(' . $type . ', ' . $value . ')',
        };
    }

    /**
     * Writes the number that the user gives to a native array of one element.
     *
     * @return list<string>
     */
    private function createPointerLines(ParameterPlan $parameter): array
    {
        $native = '$native' . \ucfirst($parameter->name);
        $value = '$' . $parameter->name;
        $type = \var_export($parameter->field->type, true);
        $number = $value;

        if ($parameter->kind === TypeKind::ENUMERATION) {
            $number = $value . '->value';
        }

        if ($parameter->kind === TypeKind::BOOLEAN) {
            $number = '(int) ' . $value;
        }

        $create = $native . ' = $this->memory->toNumberArray(' . $type . ', [' . $number . ']);';

        if (!$parameter->field->isOptional) {
            return [$create, ''];
        }

        return [$native . ' = null;', 'if (' . $value . ' !== null) {', '    ' . $create, '}', ''];
    }
}
