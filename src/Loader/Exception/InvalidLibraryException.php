<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class InvalidLibraryException extends LoaderException
{
    public static function becauseLibraryNotFound(string $library, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not load [%s]

            Please make sure you have installed the most recent GPU
            drivers and that your GPU supports the Vulkan API
            MESSAGE, $library), previous: $prev);
    }

    public static function becauseUnsupportedPlatform(string $platform, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not detect library for %s
            MESSAGE, $platform), previous: $prev);
    }
}
