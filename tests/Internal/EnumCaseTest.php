<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkColorSpaceKHR;
use Serafim\Vulkan\Exception\UnknownEnumValueException;
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

    public function testUnknownValueThrowsLibraryException(): void
    {
        $this->expectException(UnknownEnumValueException::class);

        EnumCase::createFromValue(VkColorSpaceKHR::class, 0x7FFF_FFF0);
    }

    public function testStructureWithUnknownValueThrowsLibraryException(): void
    {
        $vk = self::loadVulkan();
        $data = $vk->memory->new('VkSurfaceFormatKHR');
        $data->colorSpace = 0x7FFF_FFF0;

        $this->expectException(UnknownEnumValueException::class);

        VkSurfaceFormatKHR::createFromCData($data);
    }
}
