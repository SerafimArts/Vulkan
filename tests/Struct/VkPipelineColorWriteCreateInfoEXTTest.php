<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Struct\VkPipelineColorWriteCreateInfoEXT;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkPipelineColorWriteCreateInfoEXT::class)]
final class VkPipelineColorWriteCreateInfoEXTTest extends TestCase
{
    public function testColorWriteFlagsAreWrittenAsArrayOfBooleans(): void
    {
        $memory = self::loadVulkan()->memory;

        $data = new VkPipelineColorWriteCreateInfoEXT(colorWriteEnables: [true, false, true])->toCData($memory);

        self::assertSame(3, $data->attachmentCount);
        self::assertSame([1, 0, 1], [$data->pColorWriteEnables[0], $data->pColorWriteEnables[1], $data->pColorWriteEnables[2]]);
    }
}
