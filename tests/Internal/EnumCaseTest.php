<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkColorSpaceKHR;
use Serafim\Vulkan\Internal\EnumCase;
use Serafim\Vulkan\Struct\VkSurfaceFormatKHR;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(EnumCase::class)]
final class EnumCaseTest extends TestCase
{
    public function testKnownValueIsReadAsCase(): void
    {
        self::assertSame(
            VkColorSpaceKHR::VK_COLOR_SPACE_SRGB_NONLINEAR_KHR,
            EnumCase::createFromValue(VkColorSpaceKHR::class, 0),
        );
    }

    public function testUnknownValueIsReadAsNumber(): void
    {
        self::assertSame(0x7FFF_FFF0, EnumCase::createFromValue(VkColorSpaceKHR::class, 0x7FFF_FFF0));
    }

    public function testCaseAndNumberAreWrittenAsNumber(): void
    {
        self::assertSame(0, EnumCase::getValue(VkColorSpaceKHR::VK_COLOR_SPACE_SRGB_NONLINEAR_KHR));
        self::assertSame(42, EnumCase::getValue(42));
    }

    public function testStructureKeepsUnknownValueRead(): void
    {
        $vk = self::loadVulkan();
        $data = $vk->memory->new('VkSurfaceFormatKHR');
        $data->colorSpace = 0x7FFF_FFF0;

        self::assertSame(0x7FFF_FFF0, VkSurfaceFormatKHR::createFromCData($data)->colorSpace);
    }
}
