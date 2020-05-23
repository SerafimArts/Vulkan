<?php

/**
 * This file is part of BicEngine package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bic\Vulkan\Enum;

/**
 * @since 1.0
 */
final class VkImageCreateFlagBits
{
    public const VK_IMAGE_CREATE_SPARSE_BINDING_BIT = 0x00000001;
    public const VK_IMAGE_CREATE_SPARSE_RESIDENCY_BIT = 0x00000002;
    public const VK_IMAGE_CREATE_SPARSE_ALIASED_BIT = 0x00000004;
    public const VK_IMAGE_CREATE_MUTABLE_FORMAT_BIT = 0x00000008;
    public const VK_IMAGE_CREATE_CUBE_COMPATIBLE_BIT = 0x00000010;
    public const VK_IMAGE_CREATE_ALIAS_BIT = 0x00000400;
    public const VK_IMAGE_CREATE_SPLIT_INSTANCE_BIND_REGIONS_BIT = 0x00000040;
    public const VK_IMAGE_CREATE_2D_ARRAY_COMPATIBLE_BIT = 0x00000020;
    public const VK_IMAGE_CREATE_BLOCK_TEXEL_VIEW_COMPATIBLE_BIT = 0x00000080;
    public const VK_IMAGE_CREATE_EXTENDED_USAGE_BIT = 0x00000100;
    public const VK_IMAGE_CREATE_PROTECTED_BIT = 0x00000800;
    public const VK_IMAGE_CREATE_DISJOINT_BIT = 0x00000200;
    public const VK_IMAGE_CREATE_CORNER_SAMPLED_BIT_NV = 0x00002000;
    public const VK_IMAGE_CREATE_SAMPLE_LOCATIONS_COMPATIBLE_DEPTH_BIT_EXT = 0x00001000;
    public const VK_IMAGE_CREATE_SUBSAMPLED_BIT_EXT = 0x00004000;
    public const VK_IMAGE_CREATE_SPLIT_INSTANCE_BIND_REGIONS_BIT_KHR = self::VK_IMAGE_CREATE_SPLIT_INSTANCE_BIND_REGIONS_BIT;
    public const VK_IMAGE_CREATE_2D_ARRAY_COMPATIBLE_BIT_KHR = self::VK_IMAGE_CREATE_2D_ARRAY_COMPATIBLE_BIT;
    public const VK_IMAGE_CREATE_BLOCK_TEXEL_VIEW_COMPATIBLE_BIT_KHR = self::VK_IMAGE_CREATE_BLOCK_TEXEL_VIEW_COMPATIBLE_BIT;
    public const VK_IMAGE_CREATE_EXTENDED_USAGE_BIT_KHR = self::VK_IMAGE_CREATE_EXTENDED_USAGE_BIT;
    public const VK_IMAGE_CREATE_DISJOINT_BIT_KHR = self::VK_IMAGE_CREATE_DISJOINT_BIT;
    public const VK_IMAGE_CREATE_ALIAS_BIT_KHR = self::VK_IMAGE_CREATE_ALIAS_BIT;
    public const VK_IMAGE_CREATE_FLAG_BITS_MAX_ENUM = 0x7FFFFFFF;
}
