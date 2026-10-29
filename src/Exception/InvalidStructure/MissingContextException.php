<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception\InvalidStructure;

use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * A returned structure that holds handles is read without the object that returned them.
 */
final class MissingContextException extends InvalidStructureException
{
    public static function becauseContextIsMissing(string $structure, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The structure %s holds handles, it is created with the Vulkan object that returned them
            MESSAGE, $structure), previous: $prev);
    }
}
