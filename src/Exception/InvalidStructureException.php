<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

/**
 * A structure of the user cannot be written to native memory.
 */
final class InvalidStructureException extends VulkanException
{
    public static function becauseArraysHaveDifferentLengths(
        string $structure,
        string $member,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The arrays of the structure %s share one length, the member %s has a different number of elements
            MESSAGE, $structure, $member), previous: $prev);
    }

    public static function becauseArrayIsTooLong(
        string $structure,
        string $member,
        int $size,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The member %s of the structure %s holds at most %d elements
            MESSAGE, $member, $structure, $size), previous: $prev);
    }

    public static function becauseArrayHasWrongLength(
        string $structure,
        string $member,
        int $length,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The member %s of the structure %s must have %d elements
            MESSAGE, $member, $structure, $length), previous: $prev);
    }

    public static function becauseContextIsMissing(string $structure, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The structure %s holds handles, it is created with the Vulkan object that returned them
            MESSAGE, $structure), previous: $prev);
    }

    public static function becauseStructureCannotBeChained(
        string $structure,
        string $marker,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The structure %s cannot be chained, it does not implement %s
            MESSAGE, $structure, $marker), previous: $prev);
    }

    public static function becauseUnionDoesNotHaveOneMember(
        string $union,
        int $members,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The union %s holds exactly one member, %d are set
            MESSAGE, $union, $members), previous: $prev);
    }

    public static function becauseElementsAreOfDifferentTypes(
        string $expected,
        string $actual,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The array of structures consists of %s, but it contains %s
            MESSAGE, $expected, $actual), previous: $prev);
    }
}
