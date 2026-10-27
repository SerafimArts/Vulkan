<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

final class CommandNotAvailableException extends VulkanException
{
    public static function becauseCommandNotAvailable(string $command, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The command %s is not available: the version of the API or the extension that provides it is not supported
            MESSAGE, $command), previous: $prev);
    }
}
