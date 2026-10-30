<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversNothing;
use Serafim\Vulkan\Internal\Chain;
use Serafim\Vulkan\Struct\Extension\VersionedStructureInterface;
use Serafim\Vulkan\Struct\Extension\VkPhysicalDeviceProperties2Extension;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkPhysicalDevice16BitStorageFeatures;
use Serafim\Vulkan\Struct\VkPhysicalDeviceProperties2;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan11Features;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan11Properties;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Features;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Properties;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan13Features;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan13Properties;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan14Properties;
use Serafim\Vulkan\Tests\TestCase;

#[CoversNothing]
final class VersionedStructuresTest extends TestCase
{
    public function testStructureOfANewerVersionExtendsTheOlderOne(): void
    {
        self::assertSame(VkPhysicalDeviceVulkan11Properties::class, \get_parent_class(VkPhysicalDeviceVulkan12Properties::class));
        self::assertSame(VkPhysicalDeviceVulkan12Properties::class, \get_parent_class(VkPhysicalDeviceVulkan13Properties::class));
        self::assertSame(VkPhysicalDeviceVulkan13Properties::class, \get_parent_class(VkPhysicalDeviceVulkan14Properties::class));
        self::assertSame(VkPhysicalDeviceVulkan12Features::class, \get_parent_class(VkPhysicalDeviceVulkan13Features::class));
    }

    public function testAncestorsAreListedFromTheNearest(): void
    {
        self::assertSame(
            [VkPhysicalDeviceVulkan12Properties::class, VkPhysicalDeviceVulkan11Properties::class],
            VkPhysicalDeviceVulkan13Properties::getAncestors(),
        );
        self::assertFalse(\is_subclass_of(VkPhysicalDeviceVulkan11Properties::class, VersionedStructureInterface::class));
    }

    public function testNativeStructuresOfAllVersionsAreWrittenOnceAndChained(): void
    {
        $vk = self::loadVulkan();
        $tail = new VkPhysicalDevice16BitStorageFeatures(storageBuffer16BitAccess: true);

        $features = new VkPhysicalDeviceVulkan13Features(
            multiview: true,
            bufferDeviceAddress: true,
            dynamicRendering: true,
        );

        $data = new VkDeviceCreateInfo(chain: [$features, $tail])->toCData($vk->memory);

        $first = $vk->memory->ffi->cast('VkPhysicalDeviceVulkan13Features*', $data->pNext);

        self::assertSame(VkPhysicalDeviceVulkan13Features::STRUCTURE_TYPE, $first->sType);
        self::assertSame(1, $first->dynamicRendering);

        $second = $vk->memory->ffi->cast('VkPhysicalDeviceVulkan12Features*', $first->pNext);

        self::assertSame(VkPhysicalDeviceVulkan12Features::STRUCTURE_TYPE, $second->sType);
        self::assertSame(1, $second->bufferDeviceAddress);

        $third = $vk->memory->ffi->cast('VkPhysicalDeviceVulkan11Features*', $second->pNext);

        self::assertSame(VkPhysicalDeviceVulkan11Features::STRUCTURE_TYPE, $third->sType);
        self::assertSame(1, $third->multiview);

        $last = $vk->memory->ffi->cast('VkPhysicalDevice16BitStorageFeatures*', $third->pNext);

        self::assertSame(VkPhysicalDevice16BitStorageFeatures::STRUCTURE_TYPE, $last->sType);
        self::assertSame(1, $last->storageBuffer16BitAccess);
        self::assertTrue($last->pNext === null);
    }

    public function testFeaturesOfOlderVersionsAreArgumentsOfTheNewerOne(): void
    {
        $features = new VkPhysicalDeviceVulkan13Features(multiview: true, dynamicRendering: true);

        self::assertInstanceOf(VkPhysicalDeviceVulkan11Features::class, $features);
        self::assertTrue($features->multiview);
        self::assertFalse($features->bufferDeviceAddress);
    }

    public function testNativeStructuresOfTheAncestorsAreSharedByTheRequestedOnes(): void
    {
        $vk = self::loadVulkan();
        $root = $vk->memory->new('VkPhysicalDeviceProperties2');
        $root->sType = VkPhysicalDeviceProperties2::STRUCTURE_TYPE;

        $chain = Chain::allocate(
            $vk->memory,
            $root,
            [VkPhysicalDeviceVulkan13Properties::class, VkPhysicalDeviceVulkan12Properties::class],
            VkPhysicalDeviceProperties2Extension::class,
        );

        $types = [];

        for ($native = $root->pNext; $native !== null;) {
            $header = $vk->memory->ffi->cast('VkBaseOutStructure*', $native);
            $types[] = $header->sType;

            if ($header->sType === VkPhysicalDeviceVulkan12Properties::STRUCTURE_TYPE) {
                $vk->memory->ffi->cast('VkPhysicalDeviceVulkan12Properties*', $native)->driverID = 1;
            }

            $native = $header->pNext;
        }

        self::assertSame(
            [
                VkPhysicalDeviceVulkan13Properties::STRUCTURE_TYPE,
                VkPhysicalDeviceVulkan12Properties::STRUCTURE_TYPE,
                VkPhysicalDeviceVulkan11Properties::STRUCTURE_TYPE,
            ],
            $types,
        );

        [$thirteen, $twelve] = [...$chain->read()];

        self::assertInstanceOf(VkPhysicalDeviceVulkan13Properties::class, $thirteen);
        self::assertInstanceOf(VkPhysicalDeviceVulkan12Properties::class, $twelve);
    }
}
