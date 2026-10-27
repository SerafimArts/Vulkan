<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Struct\VkAccelerationStructureVersionInfoKHR;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkAccelerationStructureVersionInfoKHR::class)]
final class VkAccelerationStructureVersionInfoKHRTest extends TestCase
{
    public function testVersionDataIsWrittenAsBufferOfFixedLength(): void
    {
        $memory = self::loadVulkan()->memory;
        $version = \str_repeat("\x01\x02", 16);

        $data = new VkAccelerationStructureVersionInfoKHR($version)->toCData($memory);

        self::assertSame($version, \FFI::string($data->pVersionData, 32));
    }

    public function testVersionDataOfWrongLengthIsRejected(): void
    {
        $memory = self::loadVulkan()->memory;

        $this->expectException(InvalidStructureException::class);

        new VkAccelerationStructureVersionInfoKHR('short')->toCData($memory);
    }
}
