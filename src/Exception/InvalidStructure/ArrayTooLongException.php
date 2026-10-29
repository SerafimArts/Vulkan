<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * An array of a structure holds more elements than the native member does.
 */
final class ArrayTooLongException extends InvalidStructureException
{
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
}
