<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkPresentModeKHR;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Struct\VkImageSubresourceRange;
use Serafim\Vulkan\Struct\VkImageMemoryBarrier;
use Serafim\Vulkan\Struct\VkImageCreateInfo;
use Serafim\Vulkan\Struct\VkExtent3D;
use Serafim\Vulkan\Struct\VkClearColorValue;
use Serafim\Vulkan\Enum\VkSampleCountFlagBits;
use Serafim\Vulkan\Enum\VkPipelineStageFlagBits;
use Serafim\Vulkan\Enum\VkImageUsageFlagBits;
use Serafim\Vulkan\Enum\VkImageType;
use Serafim\Vulkan\Enum\VkImageLayout;
use Serafim\Vulkan\Enum\VkImageAspectFlagBits;
use Serafim\Vulkan\Enum\VkFormat;
use Serafim\Vulkan\Enum\VkAccessFlagBits;
use Serafim\Vulkan\Enum\VkSharingMode;
use Serafim\Vulkan\Enum\VkTimeDomainKHR;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Handle\VkPhysicalDevice;
use Serafim\Vulkan\Struct\VkApplicationInfo;
use Serafim\Vulkan\Struct\VkBufferCreateInfo;
use Serafim\Vulkan\Struct\VkCommandBufferAllocateInfo;
use Serafim\Vulkan\Struct\VkCommandBufferBeginInfo;
use Serafim\Vulkan\Struct\VkCommandPoolCreateInfo;
use Serafim\Vulkan\Enum\VkDescriptorType;
use Serafim\Vulkan\Struct\VkDescriptorPoolCreateInfo;
use Serafim\Vulkan\Struct\VkDescriptorPoolSize;
use Serafim\Vulkan\Struct\VkDescriptorSetAllocateInfo;
use Serafim\Vulkan\Struct\VkDescriptorSetLayoutBinding;
use Serafim\Vulkan\Struct\VkDescriptorSetLayoutCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Features;
use Serafim\Vulkan\Struct\VkPipelineCacheCreateInfo;
use Serafim\Vulkan\Struct\VkQueryPoolCreateInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Struct\VkMemoryAllocateInfo;
use Serafim\Vulkan\Struct\VkShaderModuleCreateInfo;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Struct\VkWin32SurfaceCreateInfoKHR;
use Serafim\Vulkan\Vulkan;
use Serafim\Vulkan\VulkanBase;

use const Serafim\Vulkan\VK_API_VERSION_1_1;
use const Serafim\Vulkan\VK_KHR_SURFACE_EXTENSION_NAME;
use const Serafim\Vulkan\VK_KHR_WIN32_SURFACE_EXTENSION_NAME;

#[CoversClass(VulkanBase::class)]
#[CoversClass(Vulkan::class)]
final class VulkanTest extends TestCase
{
    public function testPropertiesOfThePhysicalDeviceAreRead(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(applicationName: 'Vulkan test'),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $properties = $vk->vkGetPhysicalDeviceProperties($devices[0]);

        self::assertNotSame('', $properties->deviceName);
        self::assertSame(16, \strlen($properties->pipelineCacheUUID));
        self::assertGreaterThan(0, $properties->limits->maxImageDimension2D);
    }

    public function testEnumerationsAreReturnedAsAListOfTheCases(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_1),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        try {
            $domains = $vk->vkGetPhysicalDeviceCalibrateableTimeDomainsKHR($devices[0]);
        } catch (CommandFailedException) {
            self::markTestSkipped('The calibrated timestamps are not supported');
        }

        self::assertContains(VkTimeDomainKHR::VK_TIME_DOMAIN_DEVICE_KHR, $domains);
    }

    public function testPropertiesAreReadByTheQueryThatRequiresTheStructureType(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_1),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $properties = $vk->vkGetPhysicalDeviceProperties2($devices[0])->properties;
        $families = $vk->vkGetPhysicalDeviceQueueFamilyProperties2($devices[0]);

        self::assertSame($vk->vkGetPhysicalDeviceProperties($devices[0])->deviceName, $properties->deviceName);
        self::assertNotSame('', $properties->deviceName);
        self::assertGreaterThan(0, \count($families));

        foreach ($families as $family) {
            self::assertGreaterThan(0, $family->queueFamilyProperties->queueCount);
        }
    }

    public function testQueueFamiliesAreEnumeratedAndCounted(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo());
        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $families = $vk->vkGetPhysicalDeviceQueueFamilyProperties($devices[0]);

        self::assertGreaterThan(0, \count($families));
        self::assertCount(\count($families), \iterator_to_array($families));

        foreach ($families as $family) {
            self::assertGreaterThan(0, $family->queueCount);
        }
    }

    public function testNotSupportedExtensionFailsTheCommand(): void
    {
        $vk = self::loadVulkan();

        $this->expectException(CommandFailedException::class);

        $vk->vkCreateInstance(new VkInstanceCreateInfo(enabledExtensionNames: ['VK_NOT_EXISTING_extension']));
    }

    public function testCommandsWithoutHandlesAreCalled(): void
    {
        $vk = self::loadVulkan();

        self::assertGreaterThanOrEqual(1 << 22, $vk->vkEnumerateInstanceVersion());
        self::assertGreaterThanOrEqual(0, \count($vk->vkEnumerateInstanceExtensionProperties()));
    }

    public function testMemoryPropertiesContainTheTypesOfTheMemory(): void
    {
        $vk = self::loadVulkan();
        $properties = $vk->vkGetPhysicalDeviceMemoryProperties(self::getPhysicalDevice($vk));

        self::assertGreaterThan(0, $properties->memoryTypeCount);
        self::assertCount(32, $properties->memoryTypes);
    }

    public function testBufferIsBackedByTheAllocatedMemory(): void
    {
        $vk = self::loadVulkan();
        $physicalDevice = self::getPhysicalDevice($vk);

        $device = $vk->vkCreateDevice($physicalDevice, new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $queue = $vk->vkGetDeviceQueue($device, 0, 0);
        $vk->vkQueueWaitIdle($queue);

        $buffer = $vk->vkCreateBuffer($device, new VkBufferCreateInfo(
            sharingMode: VkSharingMode::VK_SHARING_MODE_EXCLUSIVE,
            size: 4096,
            usage: 1,
        ));

        $requirements = $vk->vkGetBufferMemoryRequirements($device, $buffer);

        self::assertGreaterThanOrEqual(4096, $requirements->size);
        self::assertGreaterThan(0, $requirements->memoryTypeBits);

        $type = (int) \log($requirements->memoryTypeBits & -$requirements->memoryTypeBits, 2);
        $memory = $vk->vkAllocateMemory($device, new VkMemoryAllocateInfo(
            allocationSize: $requirements->size,
            memoryTypeIndex: $type,
        ));

        $vk->vkBindBufferMemory($device, $buffer, $memory, 0);
        $vk->vkDeviceWaitIdle($device);

        self::assertSame($device, $buffer->parent);
    }

    public function testSuccessCodeOfTheCommandIsReturned(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());

        self::assertSame(VkResult::VK_NOT_READY, $vk->vkGetFenceStatus($device, $fence));
    }

    public function testQueueFillsTheMappedBuffer(): void
    {
        $vk = self::loadVulkan();
        $physicalDevice = self::getPhysicalDevice($vk);
        $properties = $vk->vkGetPhysicalDeviceMemoryProperties($physicalDevice);

        $device = $vk->vkCreateDevice($physicalDevice, new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $buffer = $vk->vkCreateBuffer($device, new VkBufferCreateInfo(size: 256, usage: 2));
        $requirements = $vk->vkGetBufferMemoryRequirements($device, $buffer);

        $type = null;

        foreach ($properties->memoryTypes as $index => $memoryType) {
            $isSuitable = ($requirements->memoryTypeBits & (1 << $index)) !== 0
                && ($memoryType->propertyFlags & 6) === 6;

            if ($isSuitable) {
                $type = $index;

                break;
            }
        }

        if ($type === null) {
            self::markTestSkipped('There is no host visible memory');
        }

        $memory = $vk->vkAllocateMemory($device, new VkMemoryAllocateInfo(
            allocationSize: $requirements->size,
            memoryTypeIndex: $type,
        ));
        $vk->vkBindBufferMemory($device, $buffer, $memory, 0);

        $pool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $pool,
            commandBufferCount: 1,
        ));

        self::assertCount(1, $commands);

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdFillBuffer($commands[0], $buffer, 0, 128, 0xDEADBEEF);
        $vk->vkCmdUpdateBuffer($commands[0], $buffer, 128, 'ABCDEFGH');
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);

        self::assertSame(VkResult::VK_SUCCESS, $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000));

        $pointer = $vk->vkMapMemory($device, $memory, 0, 256);

        self::assertSame("\xEF\xBE\xAD\xDE\xEF\xBE\xAD\xDE", \FFI::string($pointer, 8));
        self::assertSame('ABCDEFGH', \FFI::string($vk->ffi->cast('char*', $pointer) + 128, 8));

        $vk->vkUnmapMemory($device, $memory);
    }

    public function testQueryResultsAreReturnedWithTheStatus(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $pool = $vk->vkCreateQueryPool($device, new VkQueryPoolCreateInfo(queryCount: 1));
        $commandPool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $commandPool,
            commandBufferCount: 1,
        ));

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdResetQueryPool($commands[0], $pool, 0, 1);
        $vk->vkCmdBeginQuery($commands[0], $pool, 0);
        $vk->vkCmdEndQuery($commands[0], $pool, 0);
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);
        $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000);

        $results = $vk->vkGetQueryPoolResults($device, $pool, 0, 1, 8, 8, 1 | 2);

        self::assertSame(VkResult::VK_SUCCESS, $results->status);
        self::assertSame(8, \strlen($results->value));
    }

    public function testWin32SurfaceIsCreatedFromTheWindowOfTheSystem(): void
    {
        if (\PHP_OS_FAMILY !== 'Windows') {
            self::markTestSkipped('The Win32 surface exists on Windows only');
        }

        $vk = self::loadVulkan();
        $user32 = \FFI::cdef('void* GetDesktopWindow(void);', 'user32.dll');
        $kernel32 = \FFI::cdef('void* GetModuleHandleW(void* name);', 'kernel32.dll');

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            enabledExtensionNames: [VK_KHR_SURFACE_EXTENSION_NAME, VK_KHR_WIN32_SURFACE_EXTENSION_NAME],
        ));

        $surface = $vk->vkCreateWin32SurfaceKHR($instance, new VkWin32SurfaceCreateInfoKHR(
            hinstance: $kernel32->GetModuleHandleW(null),
            hwnd: $user32->GetDesktopWindow(),
        ));

        foreach ($vk->vkEnumeratePhysicalDevices($instance) as $device) {
            self::assertContains(
                VkPresentModeKHR::VK_PRESENT_MODE_FIFO_KHR,
                $vk->vkGetPhysicalDeviceSurfacePresentModesKHR($device, $surface),
            );

            return;
        }

        self::markTestSkipped('There are no physical devices');
    }

    public function testPresentationSupportIsReturnedAsFlag(): void
    {
        if (\PHP_OS_FAMILY !== 'Windows') {
            self::markTestSkipped('The Win32 surface exists on Windows only');
        }

        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            enabledExtensionNames: [VK_KHR_SURFACE_EXTENSION_NAME, VK_KHR_WIN32_SURFACE_EXTENSION_NAME],
        ));

        foreach ($vk->vkEnumeratePhysicalDevices($instance) as $device) {
            self::assertIsBool($vk->vkGetPhysicalDeviceWin32PresentationSupportKHR($device, 0));

            return;
        }

        self::markTestSkipped('There are no physical devices');
    }

    public function testPipelineCacheDataIsReturnedAsBytes(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $cache = $vk->vkCreatePipelineCache($device, new VkPipelineCacheCreateInfo());

        self::assertGreaterThanOrEqual(16, \strlen($vk->vkGetPipelineCacheData($device, $cache)));
    }

    public function testImageIsClearedWithTheValueOfTheUnion(): void
    {
        $vk = self::loadVulkan();
        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $image = $vk->vkCreateImage($device, new VkImageCreateInfo(
            extent: new VkExtent3D(4, 4, 1),
            imageType: VkImageType::VK_IMAGE_TYPE_2D,
            format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
            mipLevels: 1,
            arrayLayers: 1,
            samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
            usage: VkImageUsageFlagBits::VK_IMAGE_USAGE_TRANSFER_DST_BIT,
        ));

        $requirements = $vk->vkGetImageMemoryRequirements($device, $image);
        $type = \array_search(1, \array_map(
            static fn(int $index): int => $requirements->memoryTypeBits >> $index & 1,
            \range(0, 31),
        ), true);

        self::assertIsInt($type);

        $memory = $vk->vkAllocateMemory($device, new VkMemoryAllocateInfo(allocationSize: $requirements->size, memoryTypeIndex: $type));
        $vk->vkBindImageMemory($device, $image, $memory, 0);

        $pool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $pool,
            commandBufferCount: 1,
        ));
        $range = new VkImageSubresourceRange(
            VkImageAspectFlagBits::VK_IMAGE_ASPECT_COLOR_BIT,
            levelCount: 1,
            layerCount: 1,
        );

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdPipelineBarrier(
            $commands[0],
            [],
            [],
            [new VkImageMemoryBarrier(
                $image,
                $range,
                dstAccessMask: VkAccessFlagBits::VK_ACCESS_TRANSFER_WRITE_BIT,
                oldLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
                newLayout: VkImageLayout::VK_IMAGE_LAYOUT_TRANSFER_DST_OPTIMAL,
            )],
            VkPipelineStageFlagBits::VK_PIPELINE_STAGE_TOP_OF_PIPE_BIT,
            VkPipelineStageFlagBits::VK_PIPELINE_STAGE_TRANSFER_BIT,
        );
        $vk->vkCmdClearColorImage(
            $commands[0],
            $image,
            VkImageLayout::VK_IMAGE_LAYOUT_TRANSFER_DST_OPTIMAL,
            new VkClearColorValue(float32: [1.0, 0.0, 0.0, 1.0]),
            [$range],
        );
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);

        self::assertSame(VkResult::VK_SUCCESS, $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000));
    }

    public function testDeviceIsCreatedWithTheChainOfFeatures(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            next: new VkPhysicalDeviceVulkan12Features(),
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $vk->vkDeviceWaitIdle($device);

        self::assertNotNull($device->pointer);
    }

    public function testShaderModuleIsCreatedFromTheBinaryCode(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        // The compute shader with the empty `main`
        $code = \pack(
            'V*',
            0x07230203,
            0x00010000,
            0,
            5,
            0,
            0x00020011,
            1,
            0x0003000E,
            0,
            1,
            0x0005000F,
            5,
            1,
            0x6E69616D,
            0,
            0x00060010,
            1,
            17,
            1,
            1,
            1,
            0x00020013,
            2,
            0x00030021,
            3,
            2,
            0x00050036,
            2,
            1,
            0,
            3,
            0x000200F8,
            4,
            0x000100FD,
            0x00010038,
        );

        $module = $vk->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(code: $code));

        self::assertNotNull($module->pointer);
    }

    public function testDescriptorSetsAreAllocatedFromTheLayouts(): void
    {
        $vk = self::loadVulkan();

        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        $layout = $vk->vkCreateDescriptorSetLayout($device, new VkDescriptorSetLayoutCreateInfo(
            bindings: [new VkDescriptorSetLayoutBinding(
                binding: 0,
                descriptorType: VkDescriptorType::VK_DESCRIPTOR_TYPE_STORAGE_BUFFER,
                descriptorCount: 1,
                stageFlags: 0x20,
            )],
        ));

        $pool = $vk->vkCreateDescriptorPool($device, new VkDescriptorPoolCreateInfo(
            maxSets: 2,
            poolSizes: [new VkDescriptorPoolSize(VkDescriptorType::VK_DESCRIPTOR_TYPE_STORAGE_BUFFER, 2)],
        ));

        $sets = $vk->vkAllocateDescriptorSets($device, new VkDescriptorSetAllocateInfo(
            descriptorPool: $pool,
            setLayouts: [$layout, $layout],
        ));

        self::assertCount(2, $sets);
    }

    private static function getPhysicalDevice(Vulkan $vk): VkPhysicalDevice
    {
        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo());

        foreach ($vk->vkEnumeratePhysicalDevices($instance) as $device) {
            return $device;
        }

        self::markTestSkipped('There are no physical devices');
    }
}
