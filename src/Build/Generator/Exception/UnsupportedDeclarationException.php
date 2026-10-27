<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Exception;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class UnsupportedDeclarationException extends GeneratorException
{
    public static function becauseDeclarationIsNotSupported(
        string $declaration,
        string $reason,
        ?\Throwable $prev = null,
    ): self {
        return new self(\sprintf(<<<'MESSAGE'
            The declaration [%s] is not supported: %s
            MESSAGE, $declaration, $reason), previous: $prev);
    }
}
