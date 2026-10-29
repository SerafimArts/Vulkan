<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal;

use FFI\CData;
use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Internal\VkEnumerator;
use Serafim\Vulkan\Struct\VkPipelineExecutableInternalRepresentationKHR;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VkEnumerator::class)]
final class VkEnumeratorTest extends TestCase
{
    public function testBytesOfTheElementsAreAllocatedForTheSizesTheCommandHasWritten(): void
    {
        $vk = self::loadVulkan();
        $calls = 0;

        $representations = VkEnumerator::create(
            $vk,
            'vkGetPipelineExecutableInternalRepresentationsKHR',
            static function (CData $count, ?CData $items) use (&$calls): int {
                ++$calls;

                if ($items === null) {
                    $count[0] = 1;

                    return 0;
                }

                /** @var CData $item */
                $item = $items[0];

                if ($item->pData === null) {
                    // The command without the buffer writes the size.
                    $item->dataSize = 3;

                    return 0;
                }

                \FFI::memcpy($item->pData, 'spv', 3);

                return 0;
            },
            'VkPipelineExecutableInternalRepresentationKHR',
            fn(CData $item): VkPipelineExecutableInternalRepresentationKHR
                => VkPipelineExecutableInternalRepresentationKHR::createFromCData($item),
            VkPipelineExecutableInternalRepresentationKHR::STRUCTURE_TYPE,
            fn(CData $item, CData $items): bool
                => VkPipelineExecutableInternalRepresentationKHR::allocateBuffers($vk->memory, $item, $items),
        );

        $items = \iterator_to_array($representations);

        self::assertSame(3, $calls);
        self::assertCount(1, $items);
        self::assertSame('spv', $items[0]->data);
    }

    public function testCommandIsNotCalledAgainWhenNoElementNeedsABuffer(): void
    {
        $vk = self::loadVulkan();
        $calls = 0;

        VkEnumerator::create(
            $vk,
            'vkGetPipelineExecutableInternalRepresentationsKHR',
            static function (CData $count, ?CData $items) use (&$calls): int {
                ++$calls;

                if ($items === null) {
                    $count[0] = 1;
                }

                return 0;
            },
            'VkPipelineExecutableInternalRepresentationKHR',
            fn(CData $item): VkPipelineExecutableInternalRepresentationKHR
                => VkPipelineExecutableInternalRepresentationKHR::createFromCData($item),
            VkPipelineExecutableInternalRepresentationKHR::STRUCTURE_TYPE,
            fn(CData $item, CData $items): bool
                => VkPipelineExecutableInternalRepresentationKHR::allocateBuffers($vk->memory, $item, $items),
        );

        self::assertSame(2, $calls);
    }
}
