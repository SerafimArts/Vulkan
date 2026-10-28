<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkImageLayout;
use Serafim\Vulkan\Internal\Chain;
use Serafim\Vulkan\Struct\Extension\VkPhysicalDeviceProperties2Extension;
use Serafim\Vulkan\Struct\VkPhysicalDeviceHostImageCopyProperties;
use Serafim\Vulkan\Struct\VkPhysicalDeviceProperties2;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(Chain::class)]
final class ChainTest extends TestCase
{
    public function testArraysAreAllocatedForTheLengthsTheFirstCallHasWritten(): void
    {
        $vk = self::loadVulkan();
        $root = $vk->memory->new('VkPhysicalDeviceProperties2');
        $root->sType = VkPhysicalDeviceProperties2::STRUCTURE_TYPE;

        $chain = Chain::allocate(
            $vk->memory,
            $root,
            [VkPhysicalDeviceHostImageCopyProperties::class],
            VkPhysicalDeviceProperties2Extension::class,
        );

        $native = $vk->memory->ffi->cast('VkPhysicalDeviceHostImageCopyProperties*', $root->pNext);

        self::assertFalse($chain->allocateBuffers($vk->memory));

        // The first call of the command writes the lengths.
        $native->copySrcLayoutCount = 2;

        self::assertTrue($chain->allocateBuffers($vk->memory));
        self::assertNull($native->pCopyDstLayouts);

        // The second call of the command writes the elements.
        $native->pCopySrcLayouts[0] = VkImageLayout::VK_IMAGE_LAYOUT_GENERAL->value;
        $native->pCopySrcLayouts[1] = VkImageLayout::VK_IMAGE_LAYOUT_PREINITIALIZED->value;

        $host = $chain->read()[0];

        self::assertInstanceOf(VkPhysicalDeviceHostImageCopyProperties::class, $host);
        self::assertSame(
            [VkImageLayout::VK_IMAGE_LAYOUT_GENERAL, VkImageLayout::VK_IMAGE_LAYOUT_PREINITIALIZED],
            $host->copySrcLayouts,
        );
        self::assertSame([], $host->copyDstLayouts);
    }
}
