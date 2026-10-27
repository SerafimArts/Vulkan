<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Exception;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class InvalidOutputException extends GeneratorException
{
    public static function becauseFilesNotFormatted(string $output, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not bring the generated files to the code style of the project: %s
            MESSAGE, $output), previous: $prev);
    }

    public static function becauseFileNotWritable(string $pathname, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            Could not write the generated file [%s]
            MESSAGE, $pathname), previous: $prev);
    }
}
