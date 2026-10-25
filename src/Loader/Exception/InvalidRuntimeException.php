<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Exception;

final class InvalidRuntimeException extends LoaderException
{
    public static function becauseExtensionIsNotAvailable(\Throwable $prev): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The FFI extension is not available: %s
            MESSAGE, $prev->getMessage()), previous: $prev);
    }

    public static function becausePreprocessorIsNotInstalled(): self
    {
        return new self(<<<'MESSAGE'
            The ffi/preprocessor package is required to compile the Vulkan
            headers, please install it with: composer require ffi/preprocessor
            MESSAGE);
    }
}
