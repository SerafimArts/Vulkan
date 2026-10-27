<?php

declare(strict_types=1);

namespace Serafim\Vulkan;

/**
 * `#define VK_MAKE_API_VERSION(variant, major, minor, patch)`
 *
 * @api
 *
 * @param int<0, 7> $variant
 * @param int<0, 127> $major
 * @param int<0, 1023> $minor
 * @param int<0, 4095> $patch
 *
 * @return int<0, 4294967295>
 */
function vk_make_api_version(int $variant, int $major, int $minor, int $patch): int
{
    return $variant << 29 | $major << 22 | $minor << 12 | $patch;
}

/**
 * `#define VK_API_VERSION_VARIANT(version)`
 *
 * @api
 *
 * @param int<0, 4294967295> $version
 *
 * @return int<0, 7>
 */
function vk_api_version_variant(int $version): int
{
    return $version >> 29 & 0x7;
}

/**
 * `#define VK_API_VERSION_MAJOR(version)`
 *
 * @api
 *
 * @param int<0, 4294967295> $version
 *
 * @return int<0, 127>
 */
function vk_api_version_major(int $version): int
{
    return $version >> 22 & 0x7F;
}

/**
 * `#define VK_API_VERSION_MINOR(version)`
 *
 * @api
 *
 * @param int<0, 4294967295> $version
 *
 * @return int<0, 1023>
 */
function vk_api_version_minor(int $version): int
{
    return $version >> 12 & 0x3FF;
}

/**
 * `#define VK_API_VERSION_PATCH(version)`
 *
 * @api
 *
 * @param int<0, 4294967295> $version
 *
 * @return int<0, 4095>
 */
function vk_api_version_patch(int $version): int
{
    return $version & 0xFFF;
}

/**
 * Returns the unsigned decimal form of a 64-bit unsigned value (`uint64_t`,
 * `VkDeviceSize`, `VkFlags64`) that PHP holds as a bit pattern of `int`.
 *
 * @api
 *
 * @return numeric-string
 */
function vk_uint64_to_string(int $value): string
{
    if ($value >= 0) {
        return (string) $value;
    }

    $half = $value >> 1 & \PHP_INT_MAX;
    $prefix = \intdiv($half, 5);
    $digit = $half % 5 * 2 + ($value & 1);

    return $prefix . $digit;
}

/**
 * Parses an unsigned decimal number into the bit pattern of `uint64_t`.
 *
 * @api
 *
 * @throws \ValueError in case of the value is not an integer from 0 to 2^64 - 1
 */
function vk_uint64_from_string(string $value): int
{
    if (\preg_match('/^\d{1,20}$/', $value) !== 1) {
        throw new \ValueError(\sprintf('Value "%s" is not an unsigned 64-bit integer', $value));
    }

    $length = \strlen($value);

    if ($length === 20 && \strcmp($value, '18446744073709551615') > 0) {
        throw new \ValueError(\sprintf('Value "%s" is out of the range of an unsigned 64-bit integer', $value));
    }

    if ($length < 19 || $length === 19 && \strcmp($value, '9223372036854775807') <= 0) {
        return (int) $value;
    }

    $prefix = (int) \substr($value, 0, -1);
    $digit = (int) \substr($value, -1);

    $head = $prefix * 5 << 1;

    if ($head >= 0 && $digit > \PHP_INT_MAX - $head) {
        return \PHP_INT_MIN + ($digit - (\PHP_INT_MAX - $head) - 1);
    }

    return $head + $digit;
}

/**
 * Compares two 64-bit unsigned values held as bit patterns of `int`, returns
 * a negative number, zero or a positive number like the spaceship operator.
 *
 * @api
 *
 * @return int<-1, 1>
 */
function vk_uint64_compare(int $left, int $right): int
{
    return ($left ^ \PHP_INT_MIN) <=> ($right ^ \PHP_INT_MIN);
}
