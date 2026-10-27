<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Exception;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class InvalidRegistryException extends GeneratorException
{
    public static function becauseRegistryNotReadable(string $pathname, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not read the Vulkan registry from [%s]
            MESSAGE, $pathname), previous: $prev);
    }

    public static function becauseDefinitionNotFound(string $kind, string $name, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The registry does not define the %s [%s]
            MESSAGE, $kind, $name), previous: $prev);
    }
}
