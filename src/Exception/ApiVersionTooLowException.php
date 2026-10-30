<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

/**
 * A structure of a version of Vulkan is asked from a device of an instance
 * that was created for an older version: the driver would leave it empty.
 */
final class ApiVersionTooLowException extends VulkanException
{
    public static function becauseStructureNeedsNewerVersion(
        string $structure,
        int $required,
        int $actual,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The structure %s needs Vulkan %s, the instance was created for Vulkan %s, give the newer version
            to the apiVersion of the VkApplicationInfo
            MESSAGE, $structure, self::format($required), self::format($actual)), previous: $prev);
    }

    private static function format(int $version): string
    {
        return (($version >> 22) & 0x7F) . '.' . (($version >> 12) & 0x3FF);
    }
}
