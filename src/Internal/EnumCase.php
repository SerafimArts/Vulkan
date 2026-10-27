<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

/**
 * A value of a Vulkan enumeration that the driver returned: the case of the
 * enumeration, or the number itself when the specification the library was
 * generated from does not know it.
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
     * @return T|int
     */
    public static function createFromValue(string $enum, int $value): \BackedEnum|int
    {
        return $enum::tryFrom($value) ?? $value;
    }

    /**
     * Returns the number that is written to the native memory.
     */
    public static function getValue(\BackedEnum|int $case): int
    {
        if ($case instanceof \BackedEnum) {
            \assert(\is_int($case->value));

            return $case->value;
        }

        return $case;
    }
}
