<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

use Serafim\Vulkan\Enum\VkResult;

/**
 * A Vulkan command has returned an error `VkResult`, the code of the exception
 * is its value, {@see VkResult::tryFrom()} gives the case.
 */
final class CommandFailedException extends VulkanException
{
    public static function becauseCommandFailed(string $command, int $result, ?\Throwable $prev = null): self
    {
        $name = 'unknown result';
        $case = VkResult::tryFrom($result);

        if ($case !== null) {
            $name = $case->name;
        }

        return new self(\sprintf(<<<'MESSAGE'
            The command %s has failed with the result %s (%d)
            MESSAGE, $command, $name, $result), $result, $prev);
    }
}
