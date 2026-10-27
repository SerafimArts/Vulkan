<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\IntegerTypes;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * The parameters of a generated command and their docblock.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Parameters
{
    public function __construct(
        private Registry $registry,
        private TypeResolver $types,
    ) {}

    /**
     * Adds the parameters the user passes: the required ones go first.
     */
    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): void
    {
        $required = [];
        $optional = [];

        foreach ($plan->parameters as $parameter) {
            $type = $this->findType($namespace, $parameter);

            if ($type === null) {
                continue;
            }

            if ($parameter->field->isOptional && !$this->isRequiredScalar($parameter)) {
                $optional[] = [$parameter, $type];
            } else {
                $required[] = [$parameter, $type];
            }
        }

        foreach ($required as [$parameter, $type]) {
            $method->addParameter($parameter->name)->setType($type);
        }

        foreach ($optional as [$parameter, $type]) {
            $this->emitOptional($method, $parameter, $type);
        }

        // A handle is described by its type.
        $documented = \array_filter(
            [...$required, ...$optional],
            static fn(array $entry): bool => $entry[0]->role !== ParameterRole::HANDLE,
        );

        if ($documented !== []) {
            $method->addComment('');
        }

        foreach ($documented as [$parameter, $type]) {
            $method->addComment(\sprintf(
                '@param %s $%s',
                $this->createDocumentedType($namespace, $plan, $parameter, $type),
                $parameter->name,
            ));
        }
    }

    private function emitOptional(Method $method, ParameterPlan $parameter, string $type): void
    {
        if ($parameter->role === ParameterRole::INPUT_ARRAY) {
            $method->addParameter($parameter->name, [])->setType($type);

            return;
        }

        $default = $this->createScalarDefault($parameter);

        if ($default !== null) {
            $method->addParameter($parameter->name, $default)->setType($type);

            return;
        }

        $method->addParameter($parameter->name, null)->setType($type)->setNullable();
    }

    /**
     * Returns the PHP type of the parameter, or {@see null} if the user does
     * not pass it.
     */
    private function findType(PhpNamespace $namespace, ParameterPlan $parameter): ?string
    {
        $type = $parameter->field->type;

        return match ($parameter->role) {
            ParameterRole::HANDLE => Imports::importFullName($namespace, Locations::HANDLE_NAMESPACE, $type),
            ParameterRole::INPUT_STRUCTURE => Imports::importFullName($namespace, Locations::STRUCT_NAMESPACE, $type),
            ParameterRole::SCALAR => $this->getScalarType($namespace, $parameter),
            ParameterRole::INPUT_ARRAY => 'array',
            ParameterRole::INPUT_STRING, ParameterRole::INPUT_BINARY => 'string',
            default => null,
        };
    }

    private function getScalarType(PhpNamespace $namespace, ParameterPlan $parameter): string
    {
        return match ($parameter->kind) {
            TypeKind::ENUMERATION => Imports::importFullName(
                $namespace,
                Locations::ENUM_NAMESPACE,
                $parameter->field->type,
            ),
            TypeKind::FLOAT => 'float',
            TypeKind::BOOLEAN => 'bool',
            default => 'int',
        };
    }

    /**
     * Whether an optional scalar has no default: an enum without a zero case.
     */
    private function isRequiredScalar(ParameterPlan $parameter): bool
    {
        return $parameter->role === ParameterRole::SCALAR
            && $parameter->kind === TypeKind::ENUMERATION
            && $this->registry->findZeroCase($parameter->field->type) === null;
    }

    /**
     * Returns the default of an optional scalar: the zero value of C.
     */
    private function createScalarDefault(ParameterPlan $parameter): mixed
    {
        if ($parameter->role !== ParameterRole::SCALAR) {
            return null;
        }

        return match ($parameter->kind) {
            TypeKind::INTEGER => 0,
            TypeKind::FLOAT => 0.0,
            TypeKind::BOOLEAN => false,
            TypeKind::ENUMERATION => new Literal(
                $parameter->field->type . '::' . $this->registry->findZeroCase($parameter->field->type),
            ),
            default => null,
        };
    }

    /**
     * Returns the type of the parameter for the docblock: the range of an
     * integer follows the width of its C type.
     */
    private function createDocumentedType(
        PhpNamespace $namespace,
        CommandPlan $plan,
        ParameterPlan $parameter,
        string $type,
    ): string {
        if ($parameter->role === ParameterRole::INPUT_ARRAY) {
            $list = 'list';

            if ($this->isRequiredArray($plan, $parameter)) {
                $list = 'non-empty-list';
            }

            return $list . '<' . $this->createElementType($namespace, $parameter) . '>';
        }

        if ($parameter->role === ParameterRole::INPUT_STRING && !$parameter->field->isOptional) {
            return 'non-empty-string';
        }

        $nullable = $parameter->field->isOptional && $this->createScalarDefault($parameter) === null;
        $type = \substr($type, (int) \strrpos('\\' . $type, '\\'));

        if ($type !== 'int') {
            if ($nullable) {
                return $type . '|null';
            }

            return $type;
        }

        $range = IntegerTypes::create($namespace, $this->types, $parameter->field->type);

        if ($nullable) {
            return $range . '|null';
        }

        return $range;
    }

    /**
     * Whether the array must have elements: neither the array nor its length
     * is optional.
     */
    private function isRequiredArray(CommandPlan $plan, ParameterPlan $array): bool
    {
        if ($array->field->isOptional) {
            return false;
        }

        foreach ($plan->command->parameters as $candidate) {
            if ($candidate->name === $array->field->length) {
                return !$candidate->isOptional;
            }
        }

        return false;
    }

    /**
     * Returns the type of the elements of an input array.
     */
    private function createElementType(PhpNamespace $namespace, ParameterPlan $parameter): string
    {
        $type = $parameter->field->type;

        return match ($parameter->kind) {
            TypeKind::STRUCTURE => Imports::import($namespace, Locations::STRUCT_NAMESPACE, $type),
            TypeKind::HANDLE => Imports::import($namespace, Locations::HANDLE_NAMESPACE, $type),
            TypeKind::ENUMERATION => Imports::import($namespace, Locations::ENUM_NAMESPACE, $type),
            TypeKind::FLOAT => 'float',
            default => IntegerTypes::create($namespace, $this->types, $type),
        };
    }
}
