<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class InvalidPlatformException extends LoaderException
{
    public static function becauseUnsupportedFamily(
        string $family,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            Could not detect the Vulkan platform for %s
            MESSAGE, $family), previous: $prev);
    }

    public static function becausePlatformNotDetected(
        string $family,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The environment of %s does not match any known Vulkan platform
            MESSAGE, $family), previous: $prev);
    }
}
