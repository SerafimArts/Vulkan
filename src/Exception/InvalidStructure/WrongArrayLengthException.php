<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * An array of a structure does not have the exact number of elements the native member has.
 */
final class WrongArrayLengthException extends InvalidStructureException
{
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
}
