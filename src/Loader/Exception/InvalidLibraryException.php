<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class InvalidLibraryException extends LoaderException
{
    public static function becauseLibraryNotFound(
        string $family,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            Could not find the Vulkan library for %s

            Please make sure you have installed the most recent GPU
            drivers and that your GPU supports the Vulkan API
            MESSAGE, $family), previous: $prev);
    }

    public static function becauseUnsupportedFamily(
        string $family,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            Could not detect the Vulkan library for %s
            MESSAGE, $family), previous: $prev);
    }
}
