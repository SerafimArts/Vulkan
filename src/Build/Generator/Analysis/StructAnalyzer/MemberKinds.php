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
                default => null,
            };
        }

        if ($member->arraySize !== null) {
            return null;
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

        if ($type === TypeKind::STRUCTURE && $member->pointerDepth === 1 && $member->length === null) {
            return PropertyKind::STRUCTURE_POINTER;
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
