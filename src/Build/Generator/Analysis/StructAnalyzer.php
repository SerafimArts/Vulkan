<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Analysis\StructAnalyzer\MemberKinds;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Build\Generator\Registry\StructDefinition;
use Serafim\Vulkan\Struct\InputStructure;

/**
 * Decides how the members of a structure are represented in PHP.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructAnalyzer
{
    private const array INPUT_KINDS = [
        PropertyKind::INTEGER,
        PropertyKind::FLOAT,
        PropertyKind::BOOLEAN,
        PropertyKind::ENUMERATION,
        PropertyKind::STRING,
        PropertyKind::STRING_LIST,
        PropertyKind::BINARY,
        PropertyKind::NATIVE,
        PropertyKind::CALLBACK,
        PropertyKind::SAMPLE_MASK,
        PropertyKind::TEXT,
        PropertyKind::BYTES,
        PropertyKind::NUMBER_LIST,
        PropertyKind::STRUCTURE_LIST,
        PropertyKind::STRUCTURE_POINTER,
        PropertyKind::STRUCTURE_VALUE,
        PropertyKind::HANDLE,
        PropertyKind::STRUCTURE_ARRAY,
        PropertyKind::HANDLE_ARRAY,
        PropertyKind::NUMBER_ARRAY,
        PropertyKind::ENUMERATION_ARRAY,
        PropertyKind::NEXT,
    ];

    private const array DUAL_KINDS = [
        PropertyKind::INTEGER,
        PropertyKind::FLOAT,
        PropertyKind::BOOLEAN,
        PropertyKind::ENUMERATION,
        PropertyKind::STRUCTURE_VALUE,
        PropertyKind::NEXT,
    ];

    private const array OUTPUT_KINDS = [
        PropertyKind::INTEGER,
        PropertyKind::FLOAT,
        PropertyKind::BOOLEAN,
        PropertyKind::ENUMERATION,
        PropertyKind::TEXT,
        PropertyKind::BYTES,
        PropertyKind::NATIVE,
        PropertyKind::NUMBER_LIST,
        PropertyKind::STRUCTURE_VALUE,
        PropertyKind::STRUCTURE_LIST,
    ];

    /**
     * Defaults of the members for which zero is not a usable value.
     */
    private const array DEFAULTS = [
        'VkApplicationInfo::apiVersion' => 1 << 22,
    ];

    private MemberKinds $kinds;

    public function __construct(
        private Registry $registry,
        TypeResolver $types,
    ) {
        $this->kinds = new MemberKinds($types);
    }

    /**
     * @throws UnsupportedDeclarationException in case of a member cannot be
     *         represented
     */
    public function analyze(StructDefinition $struct, StructRole $role): StructPlan
    {
        if ($struct->isUnion) {
            return $this->analyzeUnion($struct, $role);
        }

        $lengths = [];

        foreach ($struct->members as $member) {
            $isSampleMask = $this->kinds->find($struct, $member) === PropertyKind::SAMPLE_MASK;

            if ($member->length !== null && !$member->isOptional && !$isSampleMask) {
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
                        InputStructure::class,
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

        return new StructPlan($struct, $role, $properties, $structureType);
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
        if ($role !== StructRole::INPUT) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $struct->name,
                'a union is written by the user, it is not read',
            );
        }

        $properties = [];

        foreach ($struct->members as $member) {
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

        if ($kind === null) {
            throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the type of the member has no PHP representation',
            );
        }

        $allowed = match ($role) {
            StructRole::INPUT => self::INPUT_KINDS,
            StructRole::OUTPUT => self::OUTPUT_KINDS,
            StructRole::DUAL => self::DUAL_KINDS,
        };

        if (!\in_array($kind, $allowed, true)) {
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
                default: self::DEFAULTS[$declaration] ?? 0,
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
                hasDefault: $isInput,
                default: '',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::STRUCTURE_LIST => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                arrayLength: $this->createArrayLength($declaration, $member),
            ),
            PropertyKind::NUMBER_LIST => $this->createNumberList($declaration, $member, $kind, $name, $isInput),
            PropertyKind::STRUCTURE_POINTER => new PropertyPlan($member, $kind, $name, null, true, true),
            PropertyKind::STRUCTURE_VALUE => new PropertyPlan($member, $kind, $name, null),
            PropertyKind::HANDLE => new PropertyPlan(
                $member,
                $kind,
                $name,
                null,
                $member->isOptional,
                $member->isOptional,
            ),
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::SAMPLE_MASK,
            PropertyKind::ENUMERATION_ARRAY => new PropertyPlan(
                $member,
                $kind,
                $name,
                'array',
                hasDefault: true,
                default: [],
            ),
            PropertyKind::NEXT => throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                $declaration,
                'the member is not described',
            ),
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
     * @return int<0, max>
     * @throws UnsupportedDeclarationException
     */
    private function createArrayLength(string $declaration, FieldDefinition $member): int
    {
        $size = $member->arraySize ?? '';
        $length = $this->registry->findConstant($size);

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
