<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use Serafim\Vulkan\Exception\UnknownEnumValueException;

/**
 * A value of a Vulkan enumeration that the driver returned.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class EnumCase
{
    private function __construct() {}

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     *
     * @return T
     * @throws UnknownEnumValueException in case the enumeration does not have the value
     */
    public static function createFromValue(string $enum, int $value): \BackedEnum
    {
        return $enum::tryFrom($value)
            ?? throw UnknownEnumValueException::becauseValueIsNotInEnumeration($enum, $value);
    }
}
