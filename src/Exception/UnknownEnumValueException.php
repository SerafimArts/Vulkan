<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Exception;

/**
 * A driver returned a value that the enumeration of the specification does
 * not know.
 */
final class UnknownEnumValueException extends VulkanException
{
    public static function becauseValueIsNotInEnumeration(string $enum, int $value, ?\Throwable $prev = null): self
    {
        return new self(\sprintf(<<<'MESSAGE'
            The driver returned the value %d that the enumeration %s does not have, the library was generated
            from an older specification than the driver implements
            MESSAGE, $value, $enum), previous: $prev);
    }
}
