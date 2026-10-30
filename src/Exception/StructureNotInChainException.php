<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

/**
 * A structure was asked from the chain of a returned structure, but the
 * command did not fill it.
 */
final class StructureNotInChainException extends VulkanException
{
    public static function becauseStructureIsNotInChain(string $structure, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The chain does not have the structure %s, ask for it in the arguments of the command
            MESSAGE, $structure), previous: $prev);
    }
}
