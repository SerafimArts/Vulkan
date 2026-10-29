<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use FFI\CData;
use PHPUnit\Framework\Attributes\CoversNothing;
use Serafim\Vulkan\Handle\VkDevice;
use Serafim\Vulkan\Tests\Support\TwoCallProcedure;
use Serafim\Vulkan\Tests\TestCase;

#[CoversNothing]
final class VkDeviceFaultInfoEXTTest extends TestCase
{
    public function testArraysAreAllocatedForTheCountsTheFirstCallHasWritten(): void
    {
        $procedure = new TwoCallProcedure(
            static fn(mixed $device, CData $counts, ?CData $info): bool => $info === null,
            static function (mixed $device, CData $counts): void {
                $counts->addressInfoCount = 2;
                $counts->vendorInfoCount = 1;
                $counts->vendorBinarySize = 3;
            },
            static function (mixed $device, CData $counts, CData $info): void {
                \FFI::memcpy($info->description, 'fault', 5);
                $info->pAddressInfos[1]->reportedAddress = 4096;
                \FFI::memcpy($info->pVendorBinaryData, "\x01\x02\x03", 3);
            },
        );
        $vk = self::loadVulkan()->withProcedure('vkGetDeviceFaultInfoEXT', $procedure);

        $fault = $vk->vkGetDeviceFaultInfoEXT(new VkDevice($vk, $vk->ffi->new('VkDevice')));

        self::assertSame(2, $procedure->calls);
        self::assertSame('fault', $fault->description);
        self::assertCount(2, $fault->addressInfos);
        self::assertSame(4096, $fault->addressInfos[1]->reportedAddress);
        self::assertCount(1, $fault->vendorInfos);
        self::assertSame("\x01\x02\x03", $fault->vendorBinaryData);
    }

    public function testArraysAreEmptyWhenNothingIsReported(): void
    {
        $procedure = new TwoCallProcedure(
            static fn(mixed $device, CData $counts, ?CData $info): bool => $info === null,
            static function (): void {},
            static function (): void {},
        );
        $vk = self::loadVulkan()->withProcedure('vkGetDeviceFaultInfoEXT', $procedure);

        $fault = $vk->vkGetDeviceFaultInfoEXT(new VkDevice($vk, $vk->ffi->new('VkDevice')));

        self::assertSame([], $fault->addressInfos);
        self::assertSame([], $fault->vendorInfos);
        self::assertSame('', $fault->vendorBinaryData);
    }
}
