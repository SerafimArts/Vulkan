<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;

/**
 * The arguments of the native command.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Arguments
{
    /**
     * @return list<string>
     */
    public function create(CommandPlan $plan): array
    {
        $arguments = [];

        foreach ($plan->parameters as $parameter) {
            $native = '$native' . \ucfirst($parameter->name);

            $arguments[] = match ($parameter->role) {
                ParameterRole::HANDLE => $this->createHandleArgument($parameter),
                ParameterRole::ALLOCATOR => 'null',
                ParameterRole::INPUT_STRUCTURE => $this->createStructureArgument($parameter, $native),
                ParameterRole::INPUT_ARRAY,
                ParameterRole::INPUT_STRING,
                ParameterRole::INPUT_BINARY,
                ParameterRole::INPUT_NATIVE,
                ParameterRole::INPUT_POINTER => $native,
                ParameterRole::INPUT_COUNT => $this->createCountArgument($plan, $parameter),
                ParameterRole::SCALAR => $this->createScalarArgument($parameter),
                ParameterRole::OUTPUT_COUNT => '\FFI::addr($count)',
                ParameterRole::OUTPUT_ARRAY, ParameterRole::OUTPUT_BINARY => $this->createOutputArrayArgument(
                    $plan,
                    $parameter,
                    $native,
                ),
                default => '\FFI::addr(' . $native . ')',
            };
        }

        return $arguments;
    }

    /**
     * Returns the arguments except the length and the elements of an output
     * array, which are the last two.
     *
     * @return list<string>
     */
    public function createLeading(CommandPlan $plan): array
    {
        $arguments = [];

        foreach ($this->create($plan) as $index => $argument) {
            $role = $plan->parameters[$index]->role;

            if ($role !== ParameterRole::OUTPUT_COUNT && !$plan->isEnumerated($plan->parameters[$index])) {
                $arguments[] = $argument;
            }
        }

        return $arguments;
    }

    private function createOutputArrayArgument(CommandPlan $plan, ParameterPlan $array, string $native): string
    {
        if ($plan->isEnumerated($array)) {
            return '$items';
        }

        return $native;
    }

    private function createCountArgument(CommandPlan $plan, ParameterPlan $count): string
    {
        foreach ($plan->parameters as $parameter) {
            if ($parameter->name === $count->counts && $parameter->role === ParameterRole::INPUT_BINARY) {
                if ($parameter->field->isOptional) {
                    return '\strlen($' . $parameter->name . ' ?? \'\')';
                }

                return '\strlen($' . $parameter->name . ')';
            }
        }

        return '\count($' . $count->counts . ')';
    }

    private function createStructureArgument(ParameterPlan $parameter, string $native): string
    {
        if ($parameter->field->isOptional) {
            return '$this->addr(' . $native . ')';
        }

        return '\FFI::addr(' . $native . ')';
    }

    private function createScalarArgument(ParameterPlan $parameter): string
    {
        return match ($parameter->kind) {
            TypeKind::ENUMERATION => '$' . $parameter->name . '->value',
            TypeKind::BOOLEAN => '(int) $' . $parameter->name,
            default => '$' . $parameter->name,
        };
    }

    private function createHandleArgument(ParameterPlan $parameter): string
    {
        if ($parameter->field->isOptional) {
            return '$' . $parameter->name . '?->pointer';
        }

        return '$' . $parameter->name . '->pointer';
    }
}
