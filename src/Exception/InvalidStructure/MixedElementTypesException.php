<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * An array of structures consists of the structures of different classes.
 */
final class MixedElementTypesException extends InvalidStructureException
{
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
