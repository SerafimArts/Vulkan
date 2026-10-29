<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use FFI\CData;
use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkDataGraphPipelinePropertyARM;
use Serafim\Vulkan\Handle\VkPipeline;
use Serafim\Vulkan\Handle\VkPipelineBinaryKHR;
use Serafim\Vulkan\Handle\VkSwapchainKHR;
use Serafim\Vulkan\Struct\VkDataGraphPipelineInfoARM;
use Serafim\Vulkan\Struct\VkPhysicalDeviceHostImageCopyProperties;
use Serafim\Vulkan\Struct\VkPipelineBinaryCreateInfoKHR;
use Serafim\Vulkan\Enum\VkPresentModeKHR;
use Serafim\Vulkan\Enum\VkDebugUtilsMessageSeverityFlagBitsEXT;
use Serafim\Vulkan\Enum\VkDebugUtilsMessageTypeFlagBitsEXT;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Struct\VkViewport;
use Serafim\Vulkan\Struct\VkPipelineViewportStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineVertexInputStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineRasterizationStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineMultisampleStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineInputAssemblyStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineColorBlendStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineColorBlendAttachmentState;
use Serafim\Vulkan\Struct\VkGraphicsPipelineCreateInfo;
use Serafim\Vulkan\Enum\VkPrimitiveTopology;
use Serafim\Vulkan\Struct\VkSubpassDescription;
use Serafim\Vulkan\Struct\VkRenderPassCreateInfo;
use Serafim\Vulkan\Struct\VkRenderPassBeginInfo;
use Serafim\Vulkan\Struct\VkRect2D;
use Serafim\Vulkan\Struct\VkOffset2D;
use Serafim\Vulkan\Struct\VkImageViewCreateInfo;
use Serafim\Vulkan\Struct\VkFramebufferCreateInfo;
use Serafim\Vulkan\Struct\VkExtent2D;
use Serafim\Vulkan\Struct\VkComponentMapping;
use Serafim\Vulkan\Struct\VkClearValue;
use Serafim\Vulkan\Struct\VkAttachmentReference;
use Serafim\Vulkan\Struct\VkAttachmentDescription;
use Serafim\Vulkan\Enum\VkSubpassContents;
use Serafim\Vulkan\Enum\VkImageViewType;
use Serafim\Vulkan\Enum\VkAttachmentStoreOp;
use Serafim\Vulkan\Enum\VkAttachmentLoadOp;
use Serafim\Vulkan\Struct\VkPipelineShaderStageCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineLayoutCreateInfo;
use Serafim\Vulkan\Struct\VkComputePipelineCreateInfo;
use Serafim\Vulkan\Enum\VkShaderStageFlagBits;
use Serafim\Vulkan\Enum\VkPipelineBindPoint;
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
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Handle\VkPhysicalDevice;
use Serafim\Vulkan\Handle\VkRenderPass;
use Serafim\Vulkan\Handle\VkFramebuffer;
use Serafim\Vulkan\Handle\VkDevice;
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
use Serafim\Vulkan\Struct\VkCalibratedTimestampInfoKHR;
use Serafim\Vulkan\Struct\VkDebugUtilsMessengerCallbackDataEXT;
use Serafim\Vulkan\Struct\VkDebugUtilsMessengerCreateInfoEXT;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan11Properties;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Features;
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Properties;
use Serafim\Vulkan\Struct\VkPipelineCacheCreateInfo;
use Serafim\Vulkan\Struct\VkQueueFamilyCheckpointPropertiesNV;
use Serafim\Vulkan\Struct\VkQueryPoolCreateInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Struct\VkMemoryAllocateInfo;
use Serafim\Vulkan\Struct\VkShaderModuleCreateInfo;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Struct\VkWin32SurfaceCreateInfoKHR;
use Serafim\Vulkan\Vulkan;
use Serafim\Vulkan\VulkanBase;

use Serafim\Vulkan\Tests\Support\TwoCallProcedure;
use const Serafim\Vulkan\VK_API_VERSION_1_1;
use const Serafim\Vulkan\VK_API_VERSION_1_2;
use const Serafim\Vulkan\VK_KHR_CALIBRATED_TIMESTAMPS_EXTENSION_NAME;
use const Serafim\Vulkan\VK_EXT_DEBUG_UTILS_EXTENSION_NAME;
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

    public function testHandlesOfTheReturnedStructureAreCreatedWithTheContext(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_1),
        ));

        $groups = \iterator_to_array($vk->vkEnumeratePhysicalDeviceGroups($instance), false);

        if ($groups === []) {
            self::markTestSkipped('There are no device groups');
        }

        $device = $groups[0]->physicalDevices[0];

        self::assertSame($groups[0]->physicalDeviceCount, \count($groups[0]->physicalDevices));
        self::assertNotSame('', $vk->vkGetPhysicalDeviceProperties($device)->deviceName);
    }

    public function testStructuresOfTheChainAreFilledByTheQuery(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_2),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $properties = $vk->vkGetPhysicalDeviceProperties2($devices[0], [VkPhysicalDeviceVulkan12Properties::class]);
        $vulkan12 = $properties->findChained(VkPhysicalDeviceVulkan12Properties::class);

        self::assertInstanceOf(VkPhysicalDeviceVulkan12Properties::class, $vulkan12);
        self::assertNotSame('', $vulkan12->driverName);
        self::assertNotSame('', $vulkan12->deviceUUID);
        self::assertCount(1, $properties->chain);
        self::assertSame($vulkan12, $properties->findChained(VkPhysicalDeviceVulkan11Properties::class));
    }

    public function testFeaturesOfTheChainAreReturnedToTheDevice(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_2),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $features = $vk->vkGetPhysicalDeviceFeatures2($devices[0], [VkPhysicalDeviceVulkan12Features::class]);
        $vulkan12 = $features->findChained(VkPhysicalDeviceVulkan12Features::class);

        self::assertInstanceOf(VkPhysicalDeviceVulkan12Features::class, $vulkan12);

        $device = $vk->vkCreateDevice($devices[0], new VkDeviceCreateInfo(
            next: $vulkan12,
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        self::assertNotNull($device->pointer);
    }

    public function testStructureThatCannotBeChainedToTheQueryIsRejected(): void
    {
        $vk = self::loadVulkan();
        $device = self::getPhysicalDevice($vk);

        $this->expectException(InvalidStructureException::class);

        $vk->vkGetPhysicalDeviceProperties2($device, [VkPhysicalDeviceVulkan12Features::class]);
    }

    public function testSeveralOutputsAreReturnedByTheirNames(): void
    {
        $vk = self::loadVulkan();
        $physicalDevice = self::getPhysicalDevice($vk);
        $extensions = [];

        foreach ($vk->vkEnumerateDeviceExtensionProperties($physicalDevice) as $extension) {
            $extensions[] = $extension->extensionName;
        }

        if (!\in_array(VK_KHR_CALIBRATED_TIMESTAMPS_EXTENSION_NAME, $extensions, true)) {
            self::markTestSkipped('The calibrated timestamps are not supported');
        }

        $device = $vk->vkCreateDevice($physicalDevice, new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
            enabledExtensionNames: [VK_KHR_CALIBRATED_TIMESTAMPS_EXTENSION_NAME],
        ));

        $result = $vk->vkGetCalibratedTimestampsKHR($device, [
            new VkCalibratedTimestampInfoKHR(timeDomain: VkTimeDomainKHR::VK_TIME_DOMAIN_DEVICE_KHR),
        ]);

        self::assertCount(1, $result['timestamps']);
        self::assertGreaterThan(0, $result['timestamps'][0]);
        self::assertGreaterThanOrEqual(0, $result['maxDeviation']);
    }

    public function testChainOfEachElementIsFilledByTheEnumeration(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            applicationInfo: new VkApplicationInfo(apiVersion: VK_API_VERSION_1_2),
        ));

        $devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

        if ($devices === []) {
            self::markTestSkipped('There are no physical devices');
        }

        $families = $vk->vkGetPhysicalDeviceQueueFamilyProperties2(
            $devices[0],
            [VkQueueFamilyCheckpointPropertiesNV::class],
        );

        self::assertGreaterThan(0, \count($families));

        foreach ($families as $family) {
            self::assertGreaterThan(0, $family->queueFamilyProperties->queueCount);
            self::assertInstanceOf(
                VkQueueFamilyCheckpointPropertiesNV::class,
                $family->findChained(VkQueueFamilyCheckpointPropertiesNV::class),
            );
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

    public function testMessengerCallsTheClosureOfTheUser(): void
    {
        $vk = self::loadVulkan();

        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
            enabledExtensionNames: [VK_EXT_DEBUG_UTILS_EXTENSION_NAME],
        ));

        $messages = [];

        $messenger = $vk->vkCreateDebugUtilsMessengerEXT($instance, new VkDebugUtilsMessengerCreateInfoEXT(
            pfnUserCallback: static function (int $severity, int $types, ?CData $data) use (&$messages): bool {
                $messages[] = [$severity, \FFI::string($data->pMessage)];

                return false;
            },
            messageSeverity: VkDebugUtilsMessageSeverityFlagBitsEXT::VK_DEBUG_UTILS_MESSAGE_SEVERITY_INFO_BIT_EXT,
            messageType: VkDebugUtilsMessageTypeFlagBitsEXT::VK_DEBUG_UTILS_MESSAGE_TYPE_GENERAL_BIT_EXT,
        ));

        $vk->vkSubmitDebugUtilsMessageEXT(
            $instance,
            VkDebugUtilsMessageSeverityFlagBitsEXT::VK_DEBUG_UTILS_MESSAGE_SEVERITY_INFO_BIT_EXT,
            VkDebugUtilsMessageTypeFlagBitsEXT::VK_DEBUG_UTILS_MESSAGE_TYPE_GENERAL_BIT_EXT,
            new VkDebugUtilsMessengerCallbackDataEXT(message: 'hello'),
        );

        self::assertSame([[VkDebugUtilsMessageSeverityFlagBitsEXT::VK_DEBUG_UTILS_MESSAGE_SEVERITY_INFO_BIT_EXT, 'hello']], $messages);
        self::assertNotNull($messenger->pointer);
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

    public function testRenderPassClearsTheAttachmentWithTheValueOfTheUnion(): void
    {
        $vk = self::loadVulkan();
        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        [$renderPass, $framebuffer] = self::createRenderTarget($vk, $device);

        $pool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $pool,
            commandBufferCount: 1,
        ));

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdBeginRenderPass(
            $commands[0],
            new VkRenderPassBeginInfo(
                renderPass: $renderPass,
                framebuffer: $framebuffer,
                renderArea: new VkRect2D(new VkOffset2D(), new VkExtent2D(4, 4)),
                clearValues: [new VkClearValue(color: new VkClearColorValue(float32: [0.0, 0.5, 1.0, 1.0]))],
            ),
            VkSubpassContents::VK_SUBPASS_CONTENTS_INLINE,
        );
        $vk->vkCmdEndRenderPass($commands[0]);
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);

        self::assertSame(VkResult::VK_SUCCESS, $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000));
    }

    public function testGraphicsPipelineDrawsInsideTheRenderPass(): void
    {
        $vk = self::loadVulkan();
        $device = $vk->vkCreateDevice(self::getPhysicalDevice($vk), new VkDeviceCreateInfo(
            queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
        ));

        [$renderPass, $framebuffer] = self::createRenderTarget($vk, $device);

        // The vertex shader writes one position, the fragment shader writes one color
        $vertex = $vk->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(code: \pack('V*',
            0x07230203, 0x00010000, 0, 12, 0,
            0x00020011, 1,
            0x0003000E, 0, 1,
            0x0006000F, 0, 1, 0x6E69616D, 0, 2,
            0x00040047, 2, 11, 0,
            0x00020013, 3,
            0x00030021, 4, 3,
            0x00030016, 5, 32,
            0x00040017, 6, 5, 4,
            0x00040020, 7, 3, 6,
            0x0004003B, 7, 2, 3,
            0x0004002B, 5, 8, 0,
            0x0004002B, 5, 9, 0x3F800000,
            0x0007002C, 6, 10, 8, 8, 8, 9,
            0x00050036, 3, 1, 0, 4,
            0x000200F8, 11,
            0x0003003E, 2, 10,
            0x000100FD,
            0x00010038,
        )));
        $fragment = $vk->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(code: \pack('V*',
            0x07230203, 0x00010000, 0, 12, 0,
            0x00020011, 1,
            0x0003000E, 0, 1,
            0x0006000F, 4, 1, 0x6E69616D, 0, 2,
            0x00030010, 1, 7,
            0x00040047, 2, 30, 0,
            0x00020013, 3,
            0x00030021, 4, 3,
            0x00030016, 5, 32,
            0x00040017, 6, 5, 4,
            0x00040020, 7, 3, 6,
            0x0004003B, 7, 2, 3,
            0x0004002B, 5, 8, 0,
            0x0004002B, 5, 9, 0x3F800000,
            0x0007002C, 6, 10, 9, 8, 8, 9,
            0x00050036, 3, 1, 0, 4,
            0x000200F8, 11,
            0x0003003E, 2, 10,
            0x000100FD,
            0x00010038,
        )));

        $layout = $vk->vkCreatePipelineLayout($device, new VkPipelineLayoutCreateInfo());
        $pipelines = $vk->vkCreateGraphicsPipelines($device, [new VkGraphicsPipelineCreateInfo(
            stages: [
                new VkPipelineShaderStageCreateInfo(
                    stage: VkShaderStageFlagBits::VK_SHADER_STAGE_VERTEX_BIT,
                    module: $vertex,
                    name: 'main',
                ),
                new VkPipelineShaderStageCreateInfo(
                    stage: VkShaderStageFlagBits::VK_SHADER_STAGE_FRAGMENT_BIT,
                    module: $fragment,
                    name: 'main',
                ),
            ],
            vertexInputState: new VkPipelineVertexInputStateCreateInfo(),
            inputAssemblyState: new VkPipelineInputAssemblyStateCreateInfo(
                topology: VkPrimitiveTopology::VK_PRIMITIVE_TOPOLOGY_TRIANGLE_LIST,
            ),
            viewportState: new VkPipelineViewportStateCreateInfo(
                viewports: [new VkViewport(0.0, 0.0, 4.0, 4.0, 0.0, 1.0)],
                scissors: [new VkRect2D(new VkOffset2D(), new VkExtent2D(4, 4))],
            ),
            rasterizationState: new VkPipelineRasterizationStateCreateInfo(lineWidth: 1.0),
            multisampleState: new VkPipelineMultisampleStateCreateInfo(
                rasterizationSamples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
                sampleMask: [0xFFFFFFFF],
            ),
            colorBlendState: new VkPipelineColorBlendStateCreateInfo(
                attachments: [new VkPipelineColorBlendAttachmentState(colorWriteMask: 15)],
            ),
            layout: $layout,
            renderPass: $renderPass,
        )]);

        self::assertCount(1, $pipelines);

        $pool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $pool,
            commandBufferCount: 1,
        ));

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdBeginRenderPass(
            $commands[0],
            new VkRenderPassBeginInfo(
                renderPass: $renderPass,
                framebuffer: $framebuffer,
                renderArea: new VkRect2D(new VkOffset2D(), new VkExtent2D(4, 4)),
                clearValues: [new VkClearValue(color: new VkClearColorValue(float32: [0.0, 0.0, 0.0, 1.0]))],
            ),
            VkSubpassContents::VK_SUBPASS_CONTENTS_INLINE,
        );
        $vk->vkCmdBindPipeline($commands[0], VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS, $pipelines[0]);
        $vk->vkCmdDraw($commands[0], 3, 1, 0, 0);
        $vk->vkCmdEndRenderPass($commands[0]);
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);

        self::assertSame(VkResult::VK_SUCCESS, $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000));
    }

    public function testComputePipelineIsCreatedAndDispatched(): void
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
        $layout = $vk->vkCreatePipelineLayout($device, new VkPipelineLayoutCreateInfo());
        $pipelines = $vk->vkCreateComputePipelines($device, [new VkComputePipelineCreateInfo(
            stage: new VkPipelineShaderStageCreateInfo(
                stage: VkShaderStageFlagBits::VK_SHADER_STAGE_COMPUTE_BIT,
                module: $module,
                name: 'main',
            ),
            layout: $layout,
        )]);

        self::assertCount(1, $pipelines);

        $pool = $vk->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: 0));
        $commands = $vk->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
            commandPool: $pool,
            commandBufferCount: 1,
        ));

        $vk->vkBeginCommandBuffer($commands[0], new VkCommandBufferBeginInfo());
        $vk->vkCmdBindPipeline($commands[0], VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_COMPUTE, $pipelines[0]);
        $vk->vkCmdDispatch($commands[0], 1, 1, 1);
        $vk->vkEndCommandBuffer($commands[0]);

        $fence = $vk->vkCreateFence($device, new VkFenceCreateInfo());
        $vk->vkQueueSubmit($vk->vkGetDeviceQueue($device, 0, 0), [new VkSubmitInfo(commandBuffers: [$commands[0]])], $fence);

        self::assertSame(VkResult::VK_SUCCESS, $vk->vkWaitForFences($device, [$fence], true, 1_000_000_000));
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

    /**
     * Creates a render pass that clears a color attachment, and a framebuffer of it.
     *
     * @return array{VkRenderPass, VkFramebuffer}
     */
    private static function createRenderTarget(Vulkan $vk, VkDevice $device): array
    {
        $image = $vk->vkCreateImage($device, new VkImageCreateInfo(
            extent: new VkExtent3D(4, 4, 1),
            imageType: VkImageType::VK_IMAGE_TYPE_2D,
            format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
            mipLevels: 1,
            arrayLayers: 1,
            samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
            usage: VkImageUsageFlagBits::VK_IMAGE_USAGE_COLOR_ATTACHMENT_BIT,
        ));

        $requirements = $vk->vkGetImageMemoryRequirements($device, $image);
        $type = \array_search(1, \array_map(
            static fn(int $index): int => $requirements->memoryTypeBits >> $index & 1,
            \range(0, 31),
        ), true);

        self::assertIsInt($type);

        $memory = $vk->vkAllocateMemory($device, new VkMemoryAllocateInfo(
            allocationSize: $requirements->size,
            memoryTypeIndex: $type,
        ));
        $vk->vkBindImageMemory($device, $image, $memory, 0);

        $view = $vk->vkCreateImageView($device, new VkImageViewCreateInfo(
            image: $image,
            components: new VkComponentMapping(),
            subresourceRange: new VkImageSubresourceRange(
                VkImageAspectFlagBits::VK_IMAGE_ASPECT_COLOR_BIT,
                levelCount: 1,
                layerCount: 1,
            ),
            viewType: VkImageViewType::VK_IMAGE_VIEW_TYPE_2D,
            format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
        ));

        $renderPass = $vk->vkCreateRenderPass($device, new VkRenderPassCreateInfo(
            attachments: [new VkAttachmentDescription(
                format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
                samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
                loadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_CLEAR,
                storeOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_STORE,
                initialLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
                finalLayout: VkImageLayout::VK_IMAGE_LAYOUT_COLOR_ATTACHMENT_OPTIMAL,
            )],
            subpasses: [new VkSubpassDescription(
                pipelineBindPoint: VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS,
                colorAttachments: [new VkAttachmentReference(
                    0,
                    VkImageLayout::VK_IMAGE_LAYOUT_COLOR_ATTACHMENT_OPTIMAL,
                )],
            )],
        ));

        $framebuffer = $vk->vkCreateFramebuffer($device, new VkFramebufferCreateInfo(
            renderPass: $renderPass,
            attachments: [$view],
            width: 4,
            height: 4,
            layers: 1,
        ));

        return [$renderPass, $framebuffer];
    }

    private static function getPhysicalDevice(Vulkan $vk): VkPhysicalDevice
    {
        $instance = $vk->vkCreateInstance(new VkInstanceCreateInfo());

        foreach ($vk->vkEnumeratePhysicalDevices($instance) as $device) {
            return $device;
        }

        self::markTestSkipped('There are no physical devices');
    }

    public function testArraysOfStructureAreAllocatedBetweenTheCallsOfTheCommand(): void
    {
        $procedure = new TwoCallProcedure(
            static fn(mixed $device, mixed $swapchain, CData $properties): bool => $properties->pTimeDomains === null,
            static function (mixed $device, mixed $swapchain, CData $properties): void {
                $properties->timeDomainCount = 2;
            },
            static function (mixed $device, mixed $swapchain, CData $properties): void {
                $properties->pTimeDomains[0] = VkTimeDomainKHR::VK_TIME_DOMAIN_CLOCK_MONOTONIC_KHR->value;
                $properties->pTimeDomains[1] = VkTimeDomainKHR::VK_TIME_DOMAIN_DEVICE_KHR->value;
                $properties->pTimeDomainIds[0] = 10;
                $properties->pTimeDomainIds[1] = 20;
            },
        );
        $vk = self::loadVulkan()->withProcedure('vkGetSwapchainTimeDomainPropertiesEXT', $procedure);

        $result = $vk->vkGetSwapchainTimeDomainPropertiesEXT(
            new VkDevice($vk, $vk->ffi->new('VkDevice')),
            new VkSwapchainKHR($vk, $vk->ffi->new('VkSwapchainKHR')),
        );

        $properties = $result['swapchainTimeDomainProperties'];

        self::assertSame(2, $procedure->calls);
        self::assertSame(
            [VkTimeDomainKHR::VK_TIME_DOMAIN_CLOCK_MONOTONIC_KHR, VkTimeDomainKHR::VK_TIME_DOMAIN_DEVICE_KHR],
            $properties->timeDomains,
        );
        self::assertSame([10, 20], $properties->timeDomainIds);
    }

    public function testHandlesAreCreatedForTheNumberTheFirstCallHasReported(): void
    {
        $base = self::loadVulkan();
        $ffi = $base->ffi;
        $procedure = new TwoCallProcedure(
            static fn(mixed $device, mixed $createInfo, mixed $allocator, CData $binaries): bool
                => $binaries->pPipelineBinaries === null,
            static function (mixed $device, mixed $createInfo, mixed $allocator, CData $binaries): void {
                $binaries->pipelineBinaryCount = 2;
            },
            static function (mixed $device, mixed $createInfo, mixed $allocator, CData $binaries) use ($ffi): void {
                $binaries->pPipelineBinaries[0] = $ffi->cast('VkPipelineBinaryKHR', 16);
                $binaries->pPipelineBinaries[1] = $ffi->cast('VkPipelineBinaryKHR', 32);
            },
        );
        $vk = $base
            ->withProcedure('vkCreatePipelineBinariesKHR', $procedure)
            ->withProcedure('vkDestroyPipelineBinaryKHR', static function (): void {});

        $binaries = $vk->vkCreatePipelineBinariesKHR(
            new VkDevice($vk, $vk->ffi->new('VkDevice')),
            new VkPipelineBinaryCreateInfoKHR(),
        );

        self::assertSame(2, $procedure->calls);
        self::assertCount(2, $binaries);
        self::assertContainsOnlyInstancesOf(VkPipelineBinaryKHR::class, $binaries);
    }

    public function testStructuresFilledInPartAreGivenTheRequestedValuesAndTheirBytesAreAllocated(): void
    {
        $calls = 0;
        $requested = null;
        $vk = self::loadVulkan()->withProcedure(
            'vkGetDataGraphPipelinePropertiesARM',
            static function (mixed $device, mixed $info, int $count, CData $properties) use (&$calls, &$requested): int {
                ++$calls;
                $requested = $properties[0]->property;

                if ($properties[0]->pData === null) {
                    $properties[0]->dataSize = 3;

                    return 0;
                }

                \FFI::memcpy($properties[0]->pData, 'abc', 3);

                return 0;
            },
        );

        $results = $vk->vkGetDataGraphPipelinePropertiesARM(
            new VkDevice($vk, $vk->ffi->new('VkDevice')),
            new VkDataGraphPipelineInfoARM(new VkPipeline($vk, $vk->ffi->new('VkPipeline'))),
            [VkDataGraphPipelinePropertyARM::VK_DATA_GRAPH_PIPELINE_PROPERTY_IDENTIFIER_ARM],
        );

        self::assertSame(2, $calls);
        self::assertSame(VkDataGraphPipelinePropertyARM::VK_DATA_GRAPH_PIPELINE_PROPERTY_IDENTIFIER_ARM->value, $requested);
        self::assertCount(1, $results);
        self::assertSame('abc', $results[0]->data);
    }

    public function testStructureOfTheChainIsFilledInTheSecondCallOfTheCommand(): void
    {
        $calls = 0;
        $base = self::loadVulkan();
        $ffi = $base->ffi;
        $vk = $base->withProcedure(
            'vkGetPhysicalDeviceProperties2',
            static function (mixed $device, CData $properties) use (&$calls, $ffi): void {
                ++$calls;

                $host = $ffi->cast('VkPhysicalDeviceHostImageCopyProperties*', $properties->pNext);

                if ($host->pCopySrcLayouts === null) {
                    $host->copySrcLayoutCount = 1;

                    return;
                }

                $host->pCopySrcLayouts[0] = VkImageLayout::VK_IMAGE_LAYOUT_GENERAL->value;
            },
        );

        $properties = $vk->vkGetPhysicalDeviceProperties2(
            new VkPhysicalDevice($vk, $vk->ffi->new('VkPhysicalDevice')),
            [VkPhysicalDeviceHostImageCopyProperties::class],
        );

        $host = $properties->findChained(VkPhysicalDeviceHostImageCopyProperties::class);

        self::assertSame(2, $calls);
        self::assertSame([VkImageLayout::VK_IMAGE_LAYOUT_GENERAL], $host?->copySrcLayouts);
    }
}
