<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Handle\VkSemaphore;
use Serafim\Vulkan\Struct\InputStructure;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Features;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Tests\TestCase;
use Serafim\Vulkan\Vulkan;

#[CoversClass(InputStructure::class)]
final class InputStructureTest extends TestCase
{
    public function testArraysShareTheLengthTheyAreWrittenWith(): void
    {
        $vk = self::loadVulkan();

        $data = new VkSubmitInfo(
            waitSemaphores: [self::createSemaphore($vk), self::createSemaphore($vk)],
            waitDstStageMask: [1, 2],
        )->toCData($vk->memory);

        self::assertSame(2, $data->waitSemaphoreCount);
    }

    public function testEmptyArrayDoesNotResetTheSharedLength(): void
    {
        $vk = self::loadVulkan();

        $data = new VkSubmitInfo(waitSemaphores: [self::createSemaphore($vk)])->toCData($vk->memory);

        self::assertSame(1, $data->waitSemaphoreCount);
    }

    public function testArraysOfDifferentLengthsAreNotWritten(): void
    {
        $vk = self::loadVulkan();
        $submit = new VkSubmitInfo(
            waitSemaphores: [self::createSemaphore($vk), self::createSemaphore($vk)],
            waitDstStageMask: [1],
        );

        $this->expectException(InvalidStructureException::class);

        $submit->toCData($vk->memory);
    }

    public function testArrayOfStructuresOfDifferentClassesIsNotWritten(): void
    {
        $vk = self::loadVulkan();

        $this->expectException(InvalidStructureException::class);

        InputStructure::createArray($vk->memory, [new VkSubmitInfo(), new VkFenceCreateInfo()]);
    }

    public function testChainedStructureIsWrittenToPNext(): void
    {
        $vk = self::loadVulkan();

        $data = new VkDeviceCreateInfo(next: new VkPhysicalDeviceVulkan12Features(drawIndirectCount: true))
            ->toCData($vk->memory);

        $chained = $vk->ffi->cast('VkPhysicalDeviceVulkan12Features*', $data->pNext);

        self::assertSame(51, $chained->sType);
        self::assertSame(1, $chained->drawIndirectCount);
    }

    public function testStructureThatCannotBeChainedIsRejected(): void
    {
        $this->expectException(\TypeError::class);

        new VkDeviceCreateInfo(next: new VkFenceCreateInfo());
    }

    private static function createSemaphore(Vulkan $vk): VkSemaphore
    {
        return new VkSemaphore($vk, $vk->ffi->new('VkSemaphore'));
    }
}
