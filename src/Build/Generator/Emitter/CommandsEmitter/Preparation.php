<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

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
                ParameterRole::INPUT_ARRAY => $this->createArrayLines($namespace, $parameter),
                ParameterRole::INPUT_STRING, ParameterRole::INPUT_BINARY => $this->createStringLines($parameter),
                ParameterRole::INPUT_STRUCTURE => $this->createStructureLines($parameter),
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
     * Writes the elements of an input array to a native array.
     *
     * @return list<string>
     */
    private function createArrayLines(PhpNamespace $namespace, ParameterPlan $parameter): array
    {
        $native = '$native' . \ucfirst($parameter->name);
        $value = '$' . $parameter->name;
        $type = \var_export($parameter->field->type, true);

        $create = match ($parameter->kind) {
            TypeKind::STRUCTURE => Imports::import($namespace, Locations::STRUCT_NAMESPACE, $parameter->field->type)
                . '::createArray($this->memory, ' . $value . ')',
            TypeKind::HANDLE => '$this->memory->toHandleArray(' . $type . ', ' . $value . ')',
            TypeKind::ENUMERATION => '$this->memory->toNumberArray(' . $type . ', \array_map(static fn('
                . Imports::import($namespace, Locations::ENUM_NAMESPACE, $parameter->field->type)
                . ' $item): int => $item->value, ' . $value . '))',
            default => '$this->memory->toNumberArray(' . $type . ', ' . $value . ')',
        };

        return [
            $native . ' = null;',
            'if (' . $value . ' !== []) {',
            '    ' . $native . ' = ' . $create . ';',
            '}',
            '',
        ];
    }
}
