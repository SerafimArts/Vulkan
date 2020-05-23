<?php

/**
 * This file is part of BicEngine package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bic\Vulkan\Enum;

final class VkConservativeRasterizationModeEXT
{
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_DISABLED_EXT = 0;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_OVERESTIMATE_EXT = 1;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_UNDERESTIMATE_EXT = 2;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_BEGIN_RANGE_EXT = self::VK_CONSERVATIVE_RASTERIZATION_MODE_DISABLED_EXT;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_END_RANGE_EXT = self::VK_CONSERVATIVE_RASTERIZATION_MODE_UNDERESTIMATE_EXT;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_RANGE_SIZE_EXT = self::VK_CONSERVATIVE_RASTERIZATION_MODE_UNDERESTIMATE_EXT - self::VK_CONSERVATIVE_RASTERIZATION_MODE_DISABLED_EXT + 1;
    public const VK_CONSERVATIVE_RASTERIZATION_MODE_MAX_ENUM_EXT = 0x7FFFFFFF;
}
