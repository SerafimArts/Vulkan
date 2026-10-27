<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer;

use Serafim\Vulkan\Build\Generator\Analysis\Names;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

/**
 * Decides what each parameter of a command is for the generated method.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ParameterRoles
{
    private const array ELEMENT_KINDS = [
        TypeKind::STRUCTURE,
        TypeKind::HANDLE,
        TypeKind::INTEGER,
        TypeKind::FLOAT,
        TypeKind::ENUMERATION,
        TypeKind::BOOLEAN,
    ];

    /**
     * The elements of the arrays that are given as the arrays of arrays.
     */
    private const array NESTED_KINDS = [
        TypeKind::STRUCTURE,
        TypeKind::INTEGER,
        TypeKind::FLOAT,
    ];

    private const array SCALAR_KINDS = [
        TypeKind::INTEGER,
        TypeKind::FLOAT,
        TypeKind::BOOLEAN,
        TypeKind::ENUMERATION,
    ];

    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * @return list<ParameterPlan>
     * @throws UnsupportedDeclarationException in case of a parameter has no
     *         role
     */
    public function create(CommandDefinition $command): array
    {
        $lengths = [];

        foreach ($command->parameters as $field) {
            if ($field->length !== null) {
                $lengths[$field->length] = true;
            }
        }

        $parameters = [];

        foreach ($command->parameters as $field) {
            $parameters[] = new ParameterPlan(
                $field,
                $this->createRole($command, $field, isset($lengths[$field->name])),
                Names::createVariableName($field->name),
                $this->types->resolve($field->type),
            );
        }

        return $this->markInputCounts($parameters);
    }

    /**
     * @throws UnsupportedDeclarationException
     */
    private function createRole(CommandDefinition $command, FieldDefinition $field, bool $isLength): ParameterRole
    {
        $kind = $this->types->resolve($field->type);

        if ($field->unsupported === null) {
            $role = $this->findRole($field, $kind, $isLength);

            if ($field->arraySize !== null) {
                $role = $this->findFixedRole($field, $kind);
            }

            if ($role !== null) {
                return $role;
            }
        }

        throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
            $command->name,
            'the parameter ' . $field->name . ' is not supported',
        );
    }

    /**
     * `const T name[N]`: the array of the fixed size that the user gives.
     */
    private function findFixedRole(FieldDefinition $field, TypeKind $kind): ?ParameterRole
    {
        $isNumbers = \in_array($kind, [TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::ENUMERATION], true);

        if ($field->pointerDepth === 0 && $field->isConst && $isNumbers && \ctype_digit($field->arraySize ?? '')) {
            return ParameterRole::INPUT_ARRAY;
        }

        return null;
    }

    private function findRole(FieldDefinition $field, TypeKind $kind, bool $isLength): ?ParameterRole
    {
        if ($field->pointerDepth === 0) {
            return match ($kind) {
                TypeKind::HANDLE => ParameterRole::HANDLE,
                TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::BOOLEAN, TypeKind::ENUMERATION => ParameterRole::SCALAR,
                TypeKind::NATIVE => ParameterRole::INPUT_NATIVE,
                default => null,
            };
        }

        if ($field->pointerDepth === 2 && $field->type === 'void' && !$field->isConst && $field->length === null) {
            return ParameterRole::OUTPUT_POINTER;
        }

        if ($kind === TypeKind::NATIVE && $field->length === null) {
            return ParameterRole::INPUT_NATIVE;
        }

        if ($field->pointerDepth === 2 && $field->isConst && $this->hasPlainLength($field)) {
            return $this->findNestedRole($kind);
        }

        if ($field->pointerDepth !== 1) {
            return null;
        }

        if ($field->type === 'VkAllocationCallbacks' && $field->isConst) {
            return ParameterRole::ALLOCATOR;
        }

        if ($field->isConst) {
            return $this->findInputRole($field, $kind);
        }

        return $this->findOutputRole($field, $kind, $isLength);
    }

    private function findInputRole(FieldDefinition $field, TypeKind $kind): ?ParameterRole
    {
        if ($kind === TypeKind::STRUCTURE && $field->length === null) {
            return ParameterRole::INPUT_STRUCTURE;
        }

        if ($kind === TypeKind::CHARACTER && $field->length === null) {
            return ParameterRole::INPUT_STRING;
        }

        if ($field->type === 'void' && $this->hasPlainLength($field)) {
            return ParameterRole::INPUT_BINARY;
        }

        $isArray = $field->length !== null
            && !$field->isNullTerminated
            && \preg_match('/^\w+(->\w+)?$/', $field->length) === 1;

        if ($isArray && \in_array($kind, self::ELEMENT_KINDS, true)) {
            return ParameterRole::INPUT_ARRAY;
        }

        $isNumber = \in_array($kind, [TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::BOOLEAN, TypeKind::ENUMERATION], true);

        if ($field->length === null && $isNumber) {
            return ParameterRole::INPUT_POINTER;
        }

        return null;
    }

    /**
     * `const T* const*`: the arrays of arrays that the user gives.
     */
    private function findNestedRole(TypeKind $kind): ?ParameterRole
    {
        if (\in_array($kind, self::NESTED_KINDS, true)) {
            return ParameterRole::INPUT_ARRAY;
        }

        return null;
    }

    private function findOutputRole(FieldDefinition $field, TypeKind $kind, bool $isLength): ?ParameterRole
    {
        if ($field->type === 'void' && $this->hasPlainLength($field)) {
            return ParameterRole::OUTPUT_BINARY;
        }

        if ($field->length !== null) {
            return match ($kind) {
                TypeKind::HANDLE, TypeKind::STRUCTURE => ParameterRole::OUTPUT_ARRAY,
                TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::ENUMERATION => ParameterRole::OUTPUT_ARRAY,
                default => null,
            };
        }

        return match (true) {
            $kind === TypeKind::HANDLE => ParameterRole::OUTPUT_HANDLE,
            $kind === TypeKind::STRUCTURE => ParameterRole::OUTPUT_STRUCTURE,
            $kind === TypeKind::INTEGER && $isLength => ParameterRole::OUTPUT_COUNT,
            \in_array($kind, self::SCALAR_KINDS, true) => ParameterRole::OUTPUT_SCALAR,
            default => null,
        };
    }

    /**
     * Whether the length is the name of another parameter.
     */
    private function hasPlainLength(FieldDefinition $field): bool
    {
        return $field->length !== null
            && !$field->isNullTerminated
            && \preg_match('/^\w+$/', $field->length) === 1;
    }

    /**
     * The length of an input array is not passed by the user: it is the
     * number of the elements of the array.
     *
     * @param list<ParameterPlan> $parameters
     *
     * @return list<ParameterPlan>
     */
    private function markInputCounts(array $parameters): array
    {
        $arrays = [];

        foreach ($parameters as $parameter) {
            $isArray = $parameter->role === ParameterRole::INPUT_ARRAY || $parameter->role === ParameterRole::INPUT_BINARY;

            if ($isArray && $parameter->field->length !== null) {
                $arrays[$parameter->field->length] ??= $parameter->name;
            }
        }

        foreach ($parameters as $i => $parameter) {
            if ($parameter->role === ParameterRole::SCALAR && isset($arrays[$parameter->field->name])) {
                $parameters[$i] = new ParameterPlan(
                    $parameter->field,
                    ParameterRole::INPUT_COUNT,
                    $parameter->name,
                    $parameter->kind,
                    $arrays[$parameter->field->name],
                );
            }
        }

        return $parameters;
    }
}
