<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis\StructAnalyzer;

use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Build\Generator\Registry\StructDefinition;

/**
 * Recognizes how a member of a structure is represented in PHP.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class MemberKinds
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    /**
     * Returns the kind of the member, or {@see null} if it has no PHP
     * representation.
     */
    public function find(StructDefinition $struct, FieldDefinition $member): ?PropertyKind
    {
        $type = $this->types->resolve($member->type);

        if ($member->arraySize !== null && $member->pointerDepth === 0) {
            if ($member->type === 'uint8_t') {
                return PropertyKind::BYTES;
            }

            return match ($type) {
                TypeKind::CHARACTER => PropertyKind::TEXT,
                TypeKind::INTEGER, TypeKind::FLOAT => PropertyKind::NUMBER_LIST,
                TypeKind::STRUCTURE => PropertyKind::STRUCTURE_LIST,
                TypeKind::HANDLE => PropertyKind::HANDLE_LIST,
                TypeKind::ENUMERATION => PropertyKind::ENUMERATION_LIST,
                default => null,
            };
        }

        if ($member->arraySize !== null) {
            return null;
        }

        if ($type === TypeKind::CALLBACK && $member->pointerDepth === 0) {
            return PropertyKind::CALLBACK;
        }

        $isBuffer = $member->type === 'void' && !$member->isConst;

        if ($type === TypeKind::NATIVE && ($member->length === null || $isBuffer)) {
            return PropertyKind::NATIVE;
        }

        if ($member->pointerDepth === 0) {
            return match ($type) {
                TypeKind::INTEGER, TypeKind::CHARACTER => PropertyKind::INTEGER,
                TypeKind::FLOAT => PropertyKind::FLOAT,
                TypeKind::BOOLEAN => PropertyKind::BOOLEAN,
                TypeKind::ENUMERATION => PropertyKind::ENUMERATION,
                TypeKind::STRUCTURE => PropertyKind::STRUCTURE_VALUE,
                TypeKind::HANDLE => PropertyKind::HANDLE,
                default => null,
            };
        }

        if ($type === TypeKind::CHARACTER && $member->pointerDepth === 1 && $member->length === null) {
            return PropertyKind::STRING;
        }

        if ($type === TypeKind::CHARACTER && $member->pointerDepth === 2 && $member->length !== null) {
            return PropertyKind::STRING_LIST;
        }

        $isResult = $type === TypeKind::STRUCTURE
            && $member->pointerDepth === 1
            && !$member->isConst
            && $this->types->isReturnedOnly($member->type);

        if ($isResult && $member->length === null) {
            return PropertyKind::RESULT_STRUCTURE;
        }

        if ($isResult && $this->hasLengthMember($struct, $member)) {
            return PropertyKind::RESULT_ARRAY;
        }

        $isFilled = $member->pointerDepth === 1
            && !$member->isConst
            && \in_array($type, [TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::ENUMERATION, TypeKind::STRUCTURE], true)
            && $this->hasLengthMember($struct, $member);

        if ($isFilled) {
            return PropertyKind::FILLED_ARRAY;
        }

        if ($type === TypeKind::STRUCTURE && $member->pointerDepth === 1 && $member->length === null) {
            return PropertyKind::STRUCTURE_POINTER;
        }

        $isNumber = \in_array($type, [TypeKind::INTEGER, TypeKind::FLOAT, TypeKind::BOOLEAN, TypeKind::ENUMERATION], true);

        if ($isNumber && $member->pointerDepth === 1 && $member->isConst && $member->length === null) {
            return PropertyKind::SCALAR_POINTER;
        }

        if ($type === TypeKind::HANDLE && $member->pointerDepth === 1 && $member->isConst && $member->length === null) {
            return PropertyKind::HANDLE_POINTER;
        }

        if ($type === TypeKind::STRUCTURE && $member->pointerDepth === 2 && $member->isConst) {
            if ($this->hasLengthMember($struct, $member)) {
                return PropertyKind::STRUCTURE_ARRAY;
            }
        }

        if (
            $member->type === 'uint8_t'
            && $member->pointerDepth === 1
            && $member->isConst
            && $member->length !== null
            && \preg_match('/^(?:\d+\s*\*\s*)?[A-Z][A-Z_0-9]*$/', $member->length) === 1
        ) {
            return PropertyKind::BYTES;
        }

        if ($member->pointerDepth === 1 && $member->isConst && $this->hasLengthMember($struct, $member)) {
            if ($member->type === 'void' || $member->lengthDivisor === 4) {
                return PropertyKind::BINARY;
            }

            if ($member->lengthDivisor === 32) {
                return PropertyKind::SAMPLE_MASK;
            }

            if ($member->lengthDivisor !== 1) {
                return null;
            }

            return match ($type) {
                TypeKind::STRUCTURE => PropertyKind::STRUCTURE_ARRAY,
                TypeKind::HANDLE => PropertyKind::HANDLE_ARRAY,
                TypeKind::INTEGER, TypeKind::FLOAT => PropertyKind::NUMBER_ARRAY,
                TypeKind::ENUMERATION => PropertyKind::ENUMERATION_ARRAY,
                default => null,
            };
        }

        return null;
    }

    /**
     * Whether the number of the elements is stored in an integer member.
     */
    private function hasLengthMember(StructDefinition $struct, FieldDefinition $member): bool
    {
        if ($member->length === null || $member->isNullTerminated) {
            return false;
        }

        foreach ($struct->members as $candidate) {
            if ($candidate->name === $member->length) {
                return $candidate->pointerDepth === 0 && $this->types->resolve($candidate->type) === TypeKind::INTEGER;
            }
        }

        return false;
    }
}
