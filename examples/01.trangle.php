<?php

declare(strict_types=1);

use Serafim\Vulkan\Enum\VkAccessFlagBits;
use Serafim\Vulkan\Enum\VkAttachmentLoadOp;
use Serafim\Vulkan\Enum\VkAttachmentStoreOp;
use Serafim\Vulkan\Enum\VkColorSpaceKHR;
use Serafim\Vulkan\Enum\VkCompositeAlphaFlagBitsKHR;
use Serafim\Vulkan\Enum\VkFenceCreateFlagBits;
use Serafim\Vulkan\Enum\VkFormat;
use Serafim\Vulkan\Enum\VkImageAspectFlagBits;
use Serafim\Vulkan\Enum\VkImageLayout;
use Serafim\Vulkan\Enum\VkImageUsageFlagBits;
use Serafim\Vulkan\Enum\VkImageViewType;
use Serafim\Vulkan\Enum\VkPipelineBindPoint;
use Serafim\Vulkan\Enum\VkPipelineStageFlagBits;
use Serafim\Vulkan\Enum\VkPresentModeKHR;
use Serafim\Vulkan\Enum\VkPrimitiveTopology;
use Serafim\Vulkan\Enum\VkQueueFlagBits;
use Serafim\Vulkan\Enum\VkSampleCountFlagBits;
use Serafim\Vulkan\Enum\VkShaderStageFlagBits;
use Serafim\Vulkan\Enum\VkSubpassContents;
use Serafim\Vulkan\Struct\VkAttachmentDescription;
use Serafim\Vulkan\Struct\VkAttachmentReference;
use Serafim\Vulkan\Struct\VkClearColorValue;
use Serafim\Vulkan\Struct\VkClearValue;
use Serafim\Vulkan\Struct\VkCommandBufferAllocateInfo;
use Serafim\Vulkan\Struct\VkCommandBufferBeginInfo;
use Serafim\Vulkan\Struct\VkCommandPoolCreateInfo;
use Serafim\Vulkan\Struct\VkComponentMapping;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;
use Serafim\Vulkan\Struct\VkExtent2D;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkFramebufferCreateInfo;
use Serafim\Vulkan\Struct\VkGraphicsPipelineCreateInfo;
use Serafim\Vulkan\Struct\VkImageSubresourceRange;
use Serafim\Vulkan\Struct\VkImageViewCreateInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Struct\VkOffset2D;
use Serafim\Vulkan\Struct\VkPipelineColorBlendAttachmentState;
use Serafim\Vulkan\Struct\VkPipelineColorBlendStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineInputAssemblyStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineLayoutCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineMultisampleStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineRasterizationStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineShaderStageCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineVertexInputStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineViewportStateCreateInfo;
use Serafim\Vulkan\Struct\VkPresentInfoKHR;
use Serafim\Vulkan\Struct\VkRect2D;
use Serafim\Vulkan\Struct\VkRenderPassBeginInfo;
use Serafim\Vulkan\Struct\VkRenderPassCreateInfo;
use Serafim\Vulkan\Struct\VkSemaphoreCreateInfo;
use Serafim\Vulkan\Struct\VkShaderModuleCreateInfo;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Struct\VkSubpassDependency;
use Serafim\Vulkan\Struct\VkSubpassDescription;
use Serafim\Vulkan\Struct\VkSwapchainCreateInfoKHR;
use Serafim\Vulkan\Struct\VkViewport;
use Serafim\Vulkan\Struct\VkWin32SurfaceCreateInfoKHR;
use Serafim\Vulkan\Vulkan;

use const Serafim\Vulkan\VK_KHR_SWAPCHAIN_EXTENSION_NAME;
use const Serafim\Vulkan\VK_SUBPASS_EXTERNAL;


require __DIR__ . '/../vendor/autoload.php';


$vulkan = Vulkan::create();
$kernel32  = FFI::cdef('void* GetModuleHandleW(void* name);', 'kernel32.dll');
// @see https://www.glfw.org/download
$glfw3     = FFI::cdef(file_get_contents(__DIR__ . '/glfw3.h'), __DIR__ . '/glfw3.dll');



$glfw3->glfwInit();
$glfw3->glfwWindowHint(0x00022001, 0x00);
$glfw3->glfwWindowHint(0x00020003, 0x00);

$window = $glfw3->glfwCreateWindow(640, 480, 'Hello', null, null);

// Shaders (SPIR-V): the triangle is hardcoded in the vertex shader
$vertexCode = pack(
    'V*',
    0x07230203, 0x00010000, 0, 46, 0,
    0x00020011, 1,
    0x0003000E, 0, 1,
    0x0008000F, 0, 1, 0x6E69616D, 0, 34, 35, 36,
    0x00040047, 34, 11, 42,
    0x00040047, 35, 11, 0,
    0x00040047, 36, 30, 0,
    0x00020013, 2,
    0x00030021, 3, 2,
    0x00030016, 4, 32,
    0x00040017, 5, 4, 2,
    0x00040017, 6, 4, 3,
    0x00040017, 7, 4, 4,
    0x00040015, 8, 32, 1,
    0x00040015, 9, 32, 0,
    0x0004002B, 9, 12, 3,
    0x0004001C, 10, 5, 12,
    0x0004001C, 11, 6, 12,
    0x0004002B, 4, 13, 0,
    0x0004002B, 4, 14, 0x3F800000,
    0x0004002B, 4, 15, 0xBF000000,
    0x0004002B, 4, 16, 0x3F000000,
    0x0005002C, 5, 17, 13, 15,
    0x0005002C, 5, 18, 16, 16,
    0x0005002C, 5, 19, 15, 16,
    0x0006002C, 10, 20, 17, 18, 19,
    0x0006002C, 6, 21, 14, 13, 13,
    0x0006002C, 6, 22, 13, 14, 13,
    0x0006002C, 6, 23, 13, 13, 14,
    0x0006002C, 11, 24, 21, 22, 23,
    0x00040020, 25, 6, 10,
    0x00040020, 26, 6, 11,
    0x00040020, 27, 6, 5,
    0x00040020, 28, 6, 6,
    0x00040020, 29, 1, 8,
    0x00040020, 30, 3, 7,
    0x00040020, 31, 3, 6,
    0x0005003B, 25, 32, 6, 20,
    0x0005003B, 26, 33, 6, 24,
    0x0004003B, 29, 34, 1,
    0x0004003B, 30, 35, 3,
    0x0004003B, 31, 36, 3,
    0x00050036, 2, 1, 0, 3,
    0x000200F8, 37,
    0x0004003D, 8, 38, 34,
    0x00050041, 27, 39, 32, 38,
    0x0004003D, 5, 40, 39,
    0x00050051, 4, 41, 40, 0,
    0x00050051, 4, 42, 40, 1,
    0x00070050, 7, 43, 41, 42, 13, 14,
    0x0003003E, 35, 43,
    0x00050041, 28, 44, 33, 38,
    0x0004003D, 6, 45, 44,
    0x0003003E, 36, 45,
    0x000100FD,
    0x00010038,
);

$fragmentCode = pack(
    'V*',
    0x07230203, 0x00010000, 0, 18, 0,
    0x00020011, 1,
    0x0003000E, 0, 1,
    0x0007000F, 4, 1, 0x6E69616D, 0, 9, 10,
    0x00030010, 1, 7,
    0x00040047, 9, 30, 0,
    0x00040047, 10, 30, 0,
    0x00020013, 2,
    0x00030021, 3, 2,
    0x00030016, 4, 32,
    0x00040017, 5, 4, 3,
    0x00040017, 6, 4, 4,
    0x00040020, 7, 1, 5,
    0x00040020, 8, 3, 6,
    0x0004003B, 7, 9, 1,
    0x0004003B, 8, 10, 3,
    0x0004002B, 4, 11, 0x3F800000,
    0x00050036, 2, 1, 0, 3,
    0x000200F8, 12,
    0x0004003D, 5, 13, 9,
    0x00050051, 4, 14, 13, 0,
    0x00050051, 4, 15, 13, 1,
    0x00050051, 4, 16, 13, 2,
    0x00070050, 6, 17, 14, 15, 16, 11,
    0x0003003E, 10, 17,
    0x000100FD,
    0x00010038,
);


// Instance and surface
$count = $glfw3->new('uint32_t');
$names = $glfw3->glfwGetRequiredInstanceExtensions(FFI::addr($count));
$extensions = [];

for ($i = 0; $i < $count->cdata; ++$i) {
    $extensions[] = FFI::string($names[$i]);
}

$instance = $vulkan->vkCreateInstance(new VkInstanceCreateInfo(
    enabledExtensionNames: $extensions,
));

$surface = $vulkan->vkCreateWin32SurfaceKHR($instance, new VkWin32SurfaceCreateInfoKHR(
    hinstance: $kernel32->GetModuleHandleW(null),
    hwnd: $glfw3->glfwGetWin32Window($window),
));

$vulkan->vkGetInstanceProcAddr('s');

// Device and queue
foreach ($vulkan->vkEnumeratePhysicalDevices($instance) as $physical) {
    foreach ($vulkan->vkGetPhysicalDeviceQueueFamilyProperties($physical) as $family => $properties) {
        if (
            ($properties->queueFlags & VkQueueFlagBits::VK_QUEUE_GRAPHICS_BIT) !== 0
            && $vulkan->vkGetPhysicalDeviceSurfaceSupportKHR($physical, $family, $surface)
        ) {
            break 2;
        }
    }
}

$device = $vulkan->vkCreateDevice($physical, new VkDeviceCreateInfo(
    queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: $family, queuePriorities: [1.0])],
    enabledExtensionNames: [VK_KHR_SWAPCHAIN_EXTENSION_NAME],
));
$queue = $vulkan->vkGetDeviceQueue($device, $family, 0);

// Swapchain
$capabilities = $vulkan->vkGetPhysicalDeviceSurfaceCapabilitiesKHR($physical, $surface);
$extent = $capabilities->currentExtent;
$format = VkFormat::VK_FORMAT_B8G8R8A8_UNORM;

$swapchain = $vulkan->vkCreateSwapchainKHR($device, new VkSwapchainCreateInfoKHR(
    surface: $surface,
    imageExtent: new VkExtent2D($extent->width, $extent->height),
    minImageCount: $capabilities->minImageCount,
    imageFormat: $format,
    imageColorSpace: VkColorSpaceKHR::VK_COLOR_SPACE_SRGB_NONLINEAR_KHR,
    imageArrayLayers: 1,
    imageUsage: VkImageUsageFlagBits::VK_IMAGE_USAGE_COLOR_ATTACHMENT_BIT,
    preTransform: $capabilities->currentTransform,
    compositeAlpha: VkCompositeAlphaFlagBitsKHR::VK_COMPOSITE_ALPHA_OPAQUE_BIT_KHR,
    presentMode: VkPresentModeKHR::VK_PRESENT_MODE_FIFO_KHR,
    clipped: true,
));

// Render pass and framebuffers
$renderPass = $vulkan->vkCreateRenderPass($device, new VkRenderPassCreateInfo(
    attachments: [new VkAttachmentDescription(
        format: $format,
        samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
        loadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_CLEAR,
        storeOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_STORE,
        stencilLoadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_DONT_CARE,
        stencilStoreOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_DONT_CARE,
        initialLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
        finalLayout: VkImageLayout::VK_IMAGE_LAYOUT_PRESENT_SRC_KHR,
    )],
    subpasses: [new VkSubpassDescription(
        pipelineBindPoint: VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS,
        colorAttachments: [new VkAttachmentReference(0, VkImageLayout::VK_IMAGE_LAYOUT_COLOR_ATTACHMENT_OPTIMAL)],
    )],
    dependencies: [new VkSubpassDependency(
        srcSubpass: VK_SUBPASS_EXTERNAL,
        dstSubpass: 0,
        srcStageMask: VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT,
        dstStageMask: VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT,
        dstAccessMask: VkAccessFlagBits::VK_ACCESS_COLOR_ATTACHMENT_WRITE_BIT,
    )],
));

$framebuffers = [];

foreach ($vulkan->vkGetSwapchainImagesKHR($device, $swapchain) as $image) {
    $view = $vulkan->vkCreateImageView($device, new VkImageViewCreateInfo(
        image: $image,
        components: new VkComponentMapping(),
        subresourceRange: new VkImageSubresourceRange(
            VkImageAspectFlagBits::VK_IMAGE_ASPECT_COLOR_BIT,
            levelCount: 1,
            layerCount: 1,
        ),
        viewType: VkImageViewType::VK_IMAGE_VIEW_TYPE_2D,
        format: $format,
    ));

    $framebuffers[] = $vulkan->vkCreateFramebuffer($device, new VkFramebufferCreateInfo(
        renderPass: $renderPass,
        attachments: [$view],
        width: $extent->width,
        height: $extent->height,
        layers: 1,
    ));
}

// Pipeline
$vertex = $vulkan->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(code: $vertexCode));
$fragment = $vulkan->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(code: $fragmentCode));

$pipeline = $vulkan->vkCreateGraphicsPipelines($device, [new VkGraphicsPipelineCreateInfo(
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
        viewports: [new VkViewport(0.0, 0.0, (float) $extent->width, (float) $extent->height, 0.0, 1.0)],
        scissors: [new VkRect2D(new VkOffset2D(), new VkExtent2D($extent->width, $extent->height))],
    ),
    rasterizationState: new VkPipelineRasterizationStateCreateInfo(lineWidth: 1.0),
    multisampleState: new VkPipelineMultisampleStateCreateInfo(
        rasterizationSamples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
    ),
    colorBlendState: new VkPipelineColorBlendStateCreateInfo(
        attachments: [new VkPipelineColorBlendAttachmentState(colorWriteMask: 0b1111)],
    ),
    layout: $vulkan->vkCreatePipelineLayout($device, new VkPipelineLayoutCreateInfo()),
    renderPass: $renderPass,
)])[0];

// Commands: one buffer per framebuffer
$pool = $vulkan->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(queueFamilyIndex: $family));
$commands = $vulkan->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
    commandPool: $pool,
    commandBufferCount: \count($framebuffers),
));

foreach ($commands as $i => $command) {
    $vulkan->vkBeginCommandBuffer($command, new VkCommandBufferBeginInfo());
    $vulkan->vkCmdBeginRenderPass(
        $command,
        new VkRenderPassBeginInfo(
            renderPass: $renderPass,
            framebuffer: $framebuffers[$i],
            renderArea: new VkRect2D(new VkOffset2D(), new VkExtent2D($extent->width, $extent->height)),
            clearValues: [new VkClearValue(color: new VkClearColorValue(float32: [0.0, 0.0, 0.0, 1.0]))],
        ),
        VkSubpassContents::VK_SUBPASS_CONTENTS_INLINE,
    );
    $vulkan->vkCmdBindPipeline($command, VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS, $pipeline);
    $vulkan->vkCmdDraw($command, 3, 1, 0, 0);
    $vulkan->vkCmdEndRenderPass($command);
    $vulkan->vkEndCommandBuffer($command);
}

// Synchronization
$imageAvailable = $vulkan->vkCreateSemaphore($device, new VkSemaphoreCreateInfo());
$renderFinished = $vulkan->vkCreateSemaphore($device, new VkSemaphoreCreateInfo());
$fence = $vulkan->vkCreateFence($device, new VkFenceCreateInfo(
    flags: VkFenceCreateFlagBits::VK_FENCE_CREATE_SIGNALED_BIT,
));

while (!$glfw3->glfwWindowShouldClose($window)) {
    $glfw3->glfwPollEvents();

    $vulkan->vkWaitForFences($device, [$fence], true, -1);
    $vulkan->vkResetFences($device, [$fence]);

    $index = $vulkan->vkAcquireNextImageKHR($device, $swapchain, -1, $imageAvailable)->value;

    $vulkan->vkQueueSubmit($queue, [new VkSubmitInfo(
        waitSemaphores: [$imageAvailable],
        waitDstStageMask: [VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT],
        commandBuffers: [$commands[$index]],
        signalSemaphores: [$renderFinished],
    )], $fence);

    $vulkan->vkQueuePresentKHR($queue, new VkPresentInfoKHR(
        waitSemaphores: [$renderFinished],
        swapchains: [$swapchain],
        imageIndices: [$index],
    ));
}

$vulkan->vkDeviceWaitIdle($device);

$glfw3->glfwDestroyWindow($window);
$glfw3->glfwTerminate();
