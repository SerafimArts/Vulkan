<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * The arrays of a structure that share one length have different numbers of elements.
 */
final class DifferentArrayLengthsException extends InvalidStructureException
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
}
