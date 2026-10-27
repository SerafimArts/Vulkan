<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Struct\VkAccelerationStructureInstanceKHR;
use Serafim\Vulkan\Struct\VkTransformMatrixKHR;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkAccelerationStructureInstanceKHR::class)]
final class VkAccelerationStructureInstanceKHRTest extends TestCase
{
    public function testBitFieldsAndMatrixAreLaidOutAsTheSpecificationRequires(): void
    {
        $memory = self::loadVulkan()->memory;

        $data = new VkAccelerationStructureInstanceKHR(
            transform: new VkTransformMatrixKHR([1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0]),
            instanceCustomIndex: 0xABCDEF,
            mask: 0xFF,
            instanceShaderBindingTableRecordOffset: 0x123456,
            flags: 0x5,
            accelerationStructureReference: -1,
        )->toCData($memory);

        $bytes = \FFI::string(\FFI::addr($data), 64);

        self::assertSame(64, \strlen($bytes));
        self::assertSame(
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0],
            \array_values(\unpack('g12', $bytes) ?: []),
        );
        self::assertSame(0xFFABCDEF, \unpack('V', $bytes, 48)[1] ?? null);
        self::assertSame(0x05123456, \unpack('V', $bytes, 52)[1] ?? null);
        self::assertSame(-1, \unpack('q', $bytes, 56)[1] ?? null);
    }
}
