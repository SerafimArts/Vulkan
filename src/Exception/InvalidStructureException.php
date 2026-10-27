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
