<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Struct\VkConvertCooperativeVectorMatrixInfoNV;
use Serafim\Vulkan\Struct\VkDeviceOrHostAddressConstKHR;
use Serafim\Vulkan\Struct\VkDeviceOrHostAddressKHR;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkConvertCooperativeVectorMatrixInfoNV::class)]
final class VkConvertCooperativeVectorMatrixInfoNVTest extends TestCase
{
    public function testDestinationSizeIsGivenToVulkanAndReadBack(): void
    {
        $memory = self::loadVulkan()->memory;
        $info = new VkConvertCooperativeVectorMatrixInfoNV(
            srcData: new VkDeviceOrHostAddressConstKHR(deviceAddress: 1),
            dstData: new VkDeviceOrHostAddressKHR(deviceAddress: 1),
            dstSize: 64,
        );

        self::assertNull($info->dstSizeWritten);

        $data = $info->toCData($memory);

        self::assertSame(64, $data->pDstSize[0]);

        $data->pDstSize[0] = 48;

        self::assertSame(48, $info->dstSizeWritten);
    }
}
