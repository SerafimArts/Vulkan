<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * A union is written with no member or with several members.
 */
final class InvalidUnionException extends InvalidStructureException
{
    public static function becauseUnionDoesNotHaveOneMember(
        string $union,
        int $members,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The union %s holds exactly one member, %d are set
            MESSAGE, $union, $members), previous: $prev);
    }
}
