<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Analysis\StructAnalyzer\MemberKinds;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Quirks;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Build\Generator\Registry\StructDefinition;
use Serafim\Vulkan\Struct\InputStructureInterface;

/**
 * Decides how the members of a structure are represented in PHP.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructAnalyzer
{
    private MemberKinds $kinds;

    public function __construct(
        private Registry $registry,
        private TypeResolver $types,
    ) {
        $this->kinds = new MemberKinds($types);
    }

    /**
     * @throws UnsupportedDeclarationException in case of a member cannot be
     *         represented
     */
    public function analyze(StructDefinition $struct, StructRole $role): StructPlan
    {
        $counted = Quirks::findCounts($struct->name);

        if ($counted !== null) {
            $struct = $this->withCounts($struct, $counted[0], $counted[1]);
        }

        if ($struct->isUnion) {
            return $this->analyzeUnion($struct, $role);
        }

        $lengths = [];

        foreach ($struct->members as $member) {
            $kind = $this->kinds->find($struct, $member);
            $isLengthKept = $kind?->keepsLength() ?? false;

            if ($member->length !== null && !$member->isOptional && !$isLengthKept) {
                $lengths[$member->length] = true;
            }
        }

        $properties = [];
        $structureType = null;

        foreach ($struct->members as $member) {
            if ($member->name === 'sType' && $member->value !== null) {
                $structureType = $member->value;

                continue;
            }

            if ($member->name === 'pNext') {
                if ($role !== StructRole::OUTPUT) {
                    $properties[] = new PropertyPlan(
                        $member,
                        PropertyKind::NEXT,
                        'next',
                        InputStructureInterface::class,
                        true,
                        true,
                    );
                }

                if ($role === StructRole::OUTPUT) {
                    $properties[] = new PropertyPlan($member, PropertyKind::CHAIN, 'chain', 'array', false, true, []);
                }

                continue;
            }

            if ($role !== StructRole::OUTPUT && isset($lengths[$member->name])) {
                continue;
            }

            if ($role !== StructRole::OUTPUT && $this->isResultsArray($member)) {
                continue;
            }

            $properties[] = $this->createProperty($struct, $member, $role);
        }

        return new StructPlan($struct, $role, $properties, $structureType, counts: $counted[0] ?? null);
    }

    /**
     * Returns the structure whose arrays have their lengths in the members of
     * another structure.
     *
     * @param non-empty-string $counts
     * @param array<non-empty-string, non-empty-string> $lengths the members
     *        with the lengths by the members with the arrays
     */
    private function withCounts(StructDefinition $struct, string $counts, array $lengths): StructDefinition
    {
        $members = [];

        foreach ($struct->members as $member) {
            $length = $lengths[$member->name] ?? null;

            if ($length === null) {
                $members[] = $member;

                continue;
            }

            $members[] = new FieldDefinition(
                $member->name,
                $member->type,
                $member->pointerDepth,
                $member->isConst,
                $member->arraySize,
                $length,
                $member->isNullTerminated,
                $member->isOptional,
                $member->value,
                $member->unsupported,
                $member->comment,
                $member->limitType,
                $member->lengthDivisor,
                $member->bits,
                $member->isMultidimensional,
                $member->bitWord,
                $member->bitOffset,
                $counts,
            );
        }

        return new StructDefinition(
            $struct->name,
            $members,
            $struct->extends,
            $struct->isUnion,
            $struct->isReturnedOnly,
        );
    }

    /**
     * A union is written by the user: the members are optional and exactly
     * one of them is given.
     *
     * @throws UnsupportedDeclarationException in case of the union is read or
     *         a member cannot be represented
     */
    private function analyzeUnion(StructDefinition $struct, StructRole $role): StructPlan
    {
        if ($role === StructRole::DUAL) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $struct->name,
                'a union is either written by the user or read, not both',
            );
        }

        $properties = [];

        foreach ($struct->members as $member) {
            if ($role === StructRole::OUTPUT) {
                if ($this->kinds->find($struct, $member)?->isPointer() ?? false) {
                    continue;
                }

                $properties[] = $this->createProperty($struct, $member, $role);

                continue;
            }

            $property = $this->createProperty($struct, $member, $role);

            $properties[] = new PropertyPlan(
                $property->field,
                $property->kind,
                $property->name,
                $property->builtinType,
                true,
                true,
                null,
                $property->arrayLength,
            );
        }

        return new StructPlan($struct, $role, $properties, null);
    }

    /**
     * Whether the member is the optional array of `VkResult` that a command
     * fills when it is given, such an array is left `NULL`.
     */
    private function isResultsArray(FieldDefinition $member): bool
    {
        return $member->type === 'VkResult'
            && $member->pointerDepth === 1
            && !$member->isConst
            && $member->isOptional;
    }

    /**
     * @throws UnsupportedDeclarationException
     */
    private function createProperty(StructDefinition $struct, FieldDefinition $member, StructRole $role): PropertyPlan
    {
        $declaration = $struct->name . '::' . $member->name;

        if ($member->unsupported !== null) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported($declaration, $member->unsupported);
        }

        $kind = $this->kinds->find($struct, $member);

        if ($kind === PropertyKind::FEEDBACK_ARRAY && $role === StructRole::OUTPUT) {
            $kind = PropertyKind::QUERIED_ARRAY;
        }

        $kind = $this->adaptKind($kind, $role, $struct, $member);

        $isFilledByVulkan = $struct->isReturnedOnly || Quirks::findMixedInput($struct->name) !== null;

        if ($role === StructRole::OUTPUT && $isFilledByVulkan && $this->kinds->isQueriedBuffer($struct, $member)) {
            $kind = PropertyKind::QUERIED_BYTES;
        }

        if ($kind === null) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the type of the member has no PHP representation',
            );
        }

        if ($kind === PropertyKind::NATIVE && $member->length !== null && $role === StructRole::OUTPUT) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the buffer is given by the user, a returned structure has none',
            );
        }

        if (!$kind->isAllowedIn($role)) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the kind of the member is not supported in this kind of structures',
            );
        }

        $name = Names::createVariableName($member->name);
        $isInput = $role !== StructRole::OUTPUT;

        return match ($kind) {
            PropertyKind::INTEGER => new PropertyPlan(
                $member,
                $kind,
                $name,
                'int',
                hasDefault: $isInput,
                default: Quirks::findDefault($declaration) ?? 0,
            ),
            PropertyKind::FLOAT => new PropertyPlan($member, $kind, $name, 'float', hasDefault: $isInput, default: 0.0),
            PropertyKind::BOOLEAN => new PropertyPlan(
                $member,
                $kind,
                $name,
                'bool',
                hasDefault: $isInput,
                default: false,
            ),
            PropertyKind::ENUMERATION => new PropertyPlan(
                $member,
                $kind,
                $name,
                null,
                hasDefault: $isInput && $this->registry->findZeroCase($member->type) !== null,
                default: $this->registry->findZeroCase($member->type),
            ),
            PropertyKind::STRING => new PropertyPlan($member, $kind, $name, 'string', true, true),
            PropertyKind::HANDLE_POINTER => new PropertyPlan($member, $kind, $name, null, true, true),
            PropertyKind::SCALAR_POINTER => new PropertyPlan(
                $member,
                $kind,
                $name,
                $this->createScalarType($member),
                true,
                true,
            ),
            PropertyKind::CALLBACK => new PropertyPlan(
                $member,
                $kind,
                $name,
                'Closure',
                $member->isOptional,
                $member->isOptional,
            ),
            PropertyKind::NATIVE => new PropertyPlan(
                $member,
                $kind,
                $name,
                'FFI\CData|int',
                $member->isOptional || !$isInput,
                $member->isOptional,
            ),
            PropertyKind::BINARY => new PropertyPlan($member, $kind, $name, 'string', hasDefault: true, default: ''),
            PropertyKind::STRING_LIST => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                hasDefault: true,
                default: [],
            ),
            PropertyKind::TEXT => new PropertyPlan(
                $member,
                $kind,
                $name,
                'string',
                hasDefault: $isInput,
                default: '',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::BYTES => new PropertyPlan(
                $member,
                $kind,
                $name,
                'string',
                hasDefault: $isInput && $member->pointerDepth === 0,
                default: '',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::ENUMERATION_LIST, PropertyKind::STRUCTURE_LIST => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::NUMBER_LIST => $this->createNumberList($declaration, $member, $kind, $name, $isInput),
            PropertyKind::STRUCTURE_POINTER => new PropertyPlan($member, $kind, $name, null, true, true),
            PropertyKind::FEEDBACK_STRUCTURE => new PropertyPlan($member, $kind, $name, null, true),
            PropertyKind::FEEDBACK_ARRAY => new PropertyPlan($member, $kind, $name, 'array'),
            PropertyKind::QUERIED_ARRAY => new PropertyPlan($member, $kind, $name, 'array'),
            PropertyKind::QUERIED_BYTES => new PropertyPlan($member, $kind, $name, 'string'),
            PropertyKind::STRUCTURE_VALUE => new PropertyPlan($member, $kind, $name, null),
            PropertyKind::HANDLE => new PropertyPlan(
                $member,
                $kind,
                $name,
                null,
                $member->isOptional || !$isInput,
                $member->isOptional,
            ),
            PropertyKind::HANDLE_LIST => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::SAMPLE_MASK,
            PropertyKind::BOOLEAN_ARRAY,
            PropertyKind::VALUE_POINTER_ARRAY,
            PropertyKind::ENUMERATION_ARRAY => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                hasDefault: true,
                default: [],
            ),
            PropertyKind::INOUT_INTEGER => new PropertyPlan($member, $kind, $name, 'int', hasDefault: true, default: 0),
            PropertyKind::OWNED_ARRAY => new PropertyPlan($member, $kind, $name, 'array'),
            PropertyKind::FEEDBACK_ENUMERATION => new PropertyPlan($member, $kind, $name, null, true),
            PropertyKind::NEXT, PropertyKind::CHAIN => throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the member is not described',
            ),
        };
    }

    /**
     * Adjusts the kind to the role of the structure: the array of an input
     * structure is written by the user even if the pointer is not constant,
     * the constant array of a returned structure is owned by the driver.
     */
    private function adaptKind(?PropertyKind $kind, StructRole $role, StructDefinition $struct, FieldDefinition $member): ?PropertyKind
    {
        if ($kind === PropertyKind::QUERIED_ARRAY && $role === StructRole::INPUT) {
            if (Quirks::findMixedInput($struct->name) !== null) {
                return $kind;
            }

            return match ($this->types->resolve($member->type)) {
                TypeKind::STRUCTURE => PropertyKind::STRUCTURE_ARRAY,
                TypeKind::ENUMERATION => PropertyKind::ENUMERATION_ARRAY,
                default => PropertyKind::NUMBER_ARRAY,
            };
        }

        $isArray = $kind === PropertyKind::STRUCTURE_ARRAY
            || $kind === PropertyKind::NUMBER_ARRAY
            || $kind === PropertyKind::ENUMERATION_ARRAY;

        if ($isArray && $role === StructRole::OUTPUT) {
            return PropertyKind::OWNED_ARRAY;
        }

        return $kind;
    }

    /**
     * Returns the PHP type of a number the pointer of a member points to,
     * {@see null} for an enum, which is a class.
     *
     * @return non-empty-string|null
     */
    private function createScalarType(FieldDefinition $member): ?string
    {
        return match ($this->types->resolve($member->type)) {
            TypeKind::FLOAT => 'float',
            TypeKind::BOOLEAN => 'bool',
            TypeKind::ENUMERATION => null,
            default => 'int',
        };
    }

    /**
     * An array of numbers of the fixed size: the input one is zeroed by default.
     *
     * @throws UnsupportedDeclarationException
     */
    private function createNumberList(
        string $declaration,
        FieldDefinition $member,
        PropertyKind $kind,
        string $name,
        bool $isInput,
    ): PropertyPlan {
        $length = $this->createArrayLength($declaration, $member);
        $zero = 0;

        if (\in_array($member->type, ['float', 'double'], true)) {
            $zero = 0.0;
        }

        return new PropertyPlan(
            $member,
            $kind,
            $name,
            'array',
            hasDefault: $isInput,
            default: \array_fill(0, $length, $zero),
            arrayLength: $length,
        );
    }

    /**
     * Evaluates the length of a buffer that is a constant or a constant
     * multiplied by a number.
     */
    private function evaluateLength(string $expression): ?int
    {
        if (\preg_match('/^(?:(\d+)\s*\*\s*)?(\w+)$/', $expression, $matches) !== 1) {
            return null;
        }

        $constant = $this->registry->findConstant($matches[2]);

        if ($constant === null) {
            return null;
        }

        return ($matches[1] === '' ? 1 : (int) $matches[1]) * $constant;
    }

    /**
     * @return int<0, max>
     * @throws UnsupportedDeclarationException
     */
    private function createArrayLength(string $declaration, FieldDefinition $member): int
    {
        $size = $member->arraySize ?? '';
        $length = $this->registry->findConstant($size);

        if ($member->arraySize === null && $member->pointerDepth === 1) {
            $length = $this->evaluateLength($member->length ?? '');
        }

        if (\ctype_digit($size)) {
            $length = (int) $size;
        }

        if ($length === null) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the size of the array is unknown',
            );
        }

        return \max(0, $length);
    }
}
