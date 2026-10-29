<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Struct\VkBindMemoryStatus;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkBindMemoryStatus::class)]
final class VkBindMemoryStatusTest extends TestCase
{
    public function testResultIsFilledByVulkanAfterTheStructureIsWritten(): void
    {
        $memory = self::loadVulkan()->memory;
        $status = new VkBindMemoryStatus();

        self::assertNull($status->result);

        $data = $status->toCData($memory);
        $data->pResult[0] = VkResult::VK_ERROR_UNKNOWN->value;

        self::assertSame(VkResult::VK_ERROR_UNKNOWN, $status->result);
    }
}
