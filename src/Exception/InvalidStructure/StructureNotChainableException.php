<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * A structure is chained to a structure it cannot extend.
 */
final class StructureNotChainableException extends InvalidStructureException
{
    public static function becauseStructureCannotBeChained(
        string $structure,
        string $marker,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The structure %s cannot be chained, it does not implement %s
            MESSAGE, $structure, $marker), previous: $prev);
    }
}
