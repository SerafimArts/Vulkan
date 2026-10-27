<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class LoadingException extends LoaderException
{
    public static function becauseFfiNotCreated(string $library, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not load the Vulkan API from [%s]: %s
            MESSAGE, $library, $prev?->getMessage() ?? ''), previous: $prev);
    }
}
