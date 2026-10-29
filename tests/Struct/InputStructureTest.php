<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversTrait;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Handle\VkSemaphore;
use Serafim\Vulkan\Struct\InputStructureTrait;
use Serafim\Vulkan\Struct\VkClearColorValue;
use Serafim\Vulkan\Struct\VkClearDepthStencilValue;
use Serafim\Vulkan\Struct\VkClearValue;
use Serafim\Vulkan\Struct\VkDebugUtilsLabelEXT;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkExtensionProperties;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkImageBlit;
use Serafim\Vulkan\Struct\VkImageSubresourceLayers;
use Serafim\Vulkan\Struct\VkImportMemoryHostPointerInfoEXT;
use Serafim\Vulkan\Struct\VkOffset3D;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Features;
use Serafim\Vulkan\Struct\VkPipelineCreationFeedbackCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineMultisampleStateCreateInfo;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Tests\TestCase;
use Serafim\Vulkan\Vulkan;

#[CoversTrait(InputStructureTrait::class)]
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

        VkSubmitInfo::createArray($vk->memory, [new VkSubmitInfo(), new VkFenceCreateInfo()]);
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

    public function testArrayOfNumbersOfTheFixedSizeIsWritten(): void
    {
        $vk = self::loadVulkan();

        $data = new VkDebugUtilsLabelEXT(color: [0.0, 0.5, 1.0])->toCData($vk->memory);

        self::assertSame(0.5, $data->color[1]);
        self::assertSame(1.0, $data->color[2]);
        self::assertSame(0.0, $data->color[3]);
    }

    public function testArrayOfStructuresOfTheFixedSizeIsWritten(): void
    {
        $vk = self::loadVulkan();
        $layers = new VkImageSubresourceLayers();

        $data = new VkImageBlit(
            srcSubresource: $layers,
            srcOffsets: [new VkOffset3D(1, 2, 3), new VkOffset3D(4, 5, 6)],
            dstSubresource: $layers,
            dstOffsets: [new VkOffset3D(), new VkOffset3D()],
        )->toCData($vk->memory);

        self::assertSame(6, $data->srcOffsets[1]->z);
    }

    public function testArrayLongerThanTheFixedSizeIsRejected(): void
    {
        $vk = self::loadVulkan();

        $this->expectException(InvalidStructureException::class);

        new VkDebugUtilsLabelEXT(color: [0.0, 0.0, 0.0, 0.0, 0.0])->toCData($vk->memory);
    }

    public function testTextOfTheFixedSizeIsWrittenWithTheTerminator(): void
    {
        $vk = self::loadVulkan();

        $data = new VkExtensionProperties(extensionName: 'subpass')->toCData($vk->memory);

        self::assertSame('subpass', \FFI::string($data->extensionName));
    }

    public function testFeedbackThatVulkanFillsIsReadFromTheMemoryTheStructureKeeps(): void
    {
        $vk = self::loadVulkan();

        $info = new VkPipelineCreationFeedbackCreateInfo(pipelineStageCreationFeedbackCount: 2);

        self::assertNull($info->pipelineCreationFeedback);
        self::assertSame([], $info->pipelineStageCreationFeedbacks);

        $data = $info->toCData($vk->memory);

        $data->pPipelineCreationFeedback->duration = 7;
        $data->pPipelineStageCreationFeedbacks[1]->duration = 9;

        self::assertSame(7, $info->pipelineCreationFeedback?->duration);
        self::assertCount(2, $info->pipelineStageCreationFeedbacks);
        self::assertSame(9, $info->pipelineStageCreationFeedbacks[1]->duration);
    }

    public function testFeedbackOfStagesIsEmptyWhenTheCountIsZero(): void
    {
        $vk = self::loadVulkan();

        $info = new VkPipelineCreationFeedbackCreateInfo();

        $info->toCData($vk->memory);

        self::assertNotNull($info->pipelineCreationFeedback);
        self::assertSame([], $info->pipelineStageCreationFeedbacks);
    }

    public function testSampleMaskFollowsTheNumberOfSamples(): void
    {
        $vk = self::loadVulkan();

        $data = new VkPipelineMultisampleStateCreateInfo(rasterizationSamples: 64, sampleMask: [1, 2])
            ->toCData($vk->memory);

        self::assertSame(2, $data->pSampleMask[1]);
    }

    public function testSampleMaskOfWrongLengthIsRejected(): void
    {
        $vk = self::loadVulkan();

        $this->expectException(InvalidStructureException::class);

        new VkPipelineMultisampleStateCreateInfo(rasterizationSamples: 4, sampleMask: [1, 2])->toCData($vk->memory);
    }

    public function testPointerOfTheSystemIsWrittenAsItIs(): void
    {
        $vk = self::loadVulkan();

        $data = new VkImportMemoryHostPointerInfoEXT(hostPointer: 4096)->toCData($vk->memory);

        self::assertTrue($data->pHostPointer == $vk->ffi->cast('void*', 4096));
    }

    public function testPointerOfTheSystemIsWrittenFromNativeMemory(): void
    {
        $vk = self::loadVulkan();
        $memory = $vk->memory->new('char[16]');
        $pointer = $vk->ffi->cast('void*', \FFI::addr($memory));

        $data = new VkImportMemoryHostPointerInfoEXT(hostPointer: $pointer)->toCData($vk->memory);

        self::assertTrue($data->pHostPointer == $pointer);
    }

    public function testMemberOfTheUnionIsWrittenToTheSharedMemory(): void
    {
        $vk = self::loadVulkan();

        $data = new VkClearValue(color: new VkClearColorValue(uint32: [1, 2, 3, 4]))->toCData($vk->memory);

        self::assertSame(3, $data->color->uint32[2]);
    }

    public function testUnionWithoutMembersIsRejected(): void
    {
        $vk = self::loadVulkan();

        $this->expectException(InvalidStructureException::class);

        new VkClearValue()->toCData($vk->memory);
    }

    public function testUnionWithSeveralMembersIsRejected(): void
    {
        $vk = self::loadVulkan();
        $value = new VkClearValue(color: new VkClearColorValue(float32: [1.0]), depthStencil: new VkClearDepthStencilValue());

        $this->expectException(InvalidStructureException::class);

        $value->toCData($vk->memory);
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
