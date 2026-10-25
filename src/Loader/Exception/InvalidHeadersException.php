<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class InvalidHeadersException extends LoaderException
{
    public static function becauseHeadersNotReadable(string $pathname, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not read the compiled Vulkan headers from [%s]
            MESSAGE, $pathname), previous: $prev);
    }

    public static function becauseHeadersNotCompiled(\Throwable $prev): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not preprocess the Vulkan headers: %s
            MESSAGE, $prev->getMessage()), previous: $prev);
    }

    public static function becauseHeadersNotWritable(string $pathname, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not write the compiled Vulkan headers to [%s]
            MESSAGE, $pathname), previous: $prev);
    }
}
