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
