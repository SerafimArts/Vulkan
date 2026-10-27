<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;

use function Serafim\Vulkan\vk_api_version_major;
use function Serafim\Vulkan\vk_api_version_minor;
use function Serafim\Vulkan\vk_api_version_patch;
use function Serafim\Vulkan\vk_api_version_variant;
use function Serafim\Vulkan\vk_make_api_version;

use const Serafim\Vulkan\VK_API_VERSION_1_3;
use const Serafim\Vulkan\VK_HEADER_VERSION;
use const Serafim\Vulkan\VK_HEADER_VERSION_COMPLETE;
use const Serafim\Vulkan\VK_KHR_SURFACE_EXTENSION_NAME;

#[CoversNothing]
final class FunctionsTest extends TestCase
{
    public function testVersionIsComposedAndSplit(): void
    {
        $version = vk_make_api_version(1, 2, 3, 4);

        self::assertSame(1, vk_api_version_variant($version));
        self::assertSame(2, vk_api_version_major($version));
        self::assertSame(3, vk_api_version_minor($version));
        self::assertSame(4, vk_api_version_patch($version));
    }

    public function testVersionConstantsFollowTheMacro(): void
    {
        self::assertSame(vk_make_api_version(0, 1, 3, 0), VK_API_VERSION_1_3);
        self::assertSame(vk_make_api_version(0, 1, 4, VK_HEADER_VERSION), VK_HEADER_VERSION_COMPLETE);
    }

    public function testExtensionNameIsDefined(): void
    {
        self::assertSame('VK_KHR_surface', VK_KHR_SURFACE_EXTENSION_NAME);
    }
}
