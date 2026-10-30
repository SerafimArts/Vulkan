<?php

declare(strict_types=1);

use Serafim\Vulkan\Enum\VkAccessFlagBits;
use Serafim\Vulkan\Enum\VkAttachmentLoadOp;
use Serafim\Vulkan\Enum\VkAttachmentStoreOp;
use Serafim\Vulkan\Enum\VkBufferUsageFlagBits;
use Serafim\Vulkan\Enum\VkColorSpaceKHR;
use Serafim\Vulkan\Enum\VkCommandPoolCreateFlagBits;
use Serafim\Vulkan\Enum\VkCompareOp;
use Serafim\Vulkan\Enum\VkCompositeAlphaFlagBitsKHR;
use Serafim\Vulkan\Enum\VkDescriptorType;
use Serafim\Vulkan\Enum\VkFenceCreateFlagBits;
use Serafim\Vulkan\Enum\VkFilter;
use Serafim\Vulkan\Enum\VkFormat;
use Serafim\Vulkan\Enum\VkImageAspectFlagBits;
use Serafim\Vulkan\Enum\VkImageLayout;
use Serafim\Vulkan\Enum\VkImageType;
use Serafim\Vulkan\Enum\VkImageUsageFlagBits;
use Serafim\Vulkan\Enum\VkImageViewType;
use Serafim\Vulkan\Enum\VkIndexType;
use Serafim\Vulkan\Enum\VkMemoryPropertyFlagBits;
use Serafim\Vulkan\Enum\VkPipelineBindPoint;
use Serafim\Vulkan\Enum\VkPipelineStageFlagBits;
use Serafim\Vulkan\Enum\VkPresentModeKHR;
use Serafim\Vulkan\Enum\VkPrimitiveTopology;
use Serafim\Vulkan\Enum\VkQueueFlagBits;
use Serafim\Vulkan\Enum\VkSampleCountFlagBits;
use Serafim\Vulkan\Enum\VkShaderStageFlagBits;
use Serafim\Vulkan\Enum\VkSubpassContents;
use Serafim\Vulkan\Enum\VkVertexInputRate;
use Serafim\Vulkan\Handle\VkBuffer;
use Serafim\Vulkan\Handle\VkDeviceMemory;
use Serafim\Vulkan\Struct\VkAttachmentDescription;
use Serafim\Vulkan\Struct\VkAttachmentReference;
use Serafim\Vulkan\Struct\VkBufferCreateInfo;
use Serafim\Vulkan\Struct\VkBufferImageCopy;
use Serafim\Vulkan\Struct\VkClearColorValue;
use Serafim\Vulkan\Struct\VkClearDepthStencilValue;
use Serafim\Vulkan\Struct\VkClearValue;
use Serafim\Vulkan\Struct\VkCommandBufferAllocateInfo;
use Serafim\Vulkan\Struct\VkCommandBufferBeginInfo;
use Serafim\Vulkan\Struct\VkCommandPoolCreateInfo;
use Serafim\Vulkan\Struct\VkComponentMapping;
use Serafim\Vulkan\Struct\VkDescriptorImageInfo;
use Serafim\Vulkan\Struct\VkDescriptorPoolCreateInfo;
use Serafim\Vulkan\Struct\VkDescriptorPoolSize;
use Serafim\Vulkan\Struct\VkDescriptorSetAllocateInfo;
use Serafim\Vulkan\Struct\VkDescriptorSetLayoutBinding;
use Serafim\Vulkan\Struct\VkDescriptorSetLayoutCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;
use Serafim\Vulkan\Struct\VkExtent2D;
use Serafim\Vulkan\Struct\VkExtent3D;
use Serafim\Vulkan\Struct\VkFenceCreateInfo;
use Serafim\Vulkan\Struct\VkFramebufferCreateInfo;
use Serafim\Vulkan\Struct\VkGraphicsPipelineCreateInfo;
use Serafim\Vulkan\Struct\VkImageCreateInfo;
use Serafim\Vulkan\Struct\VkImageMemoryBarrier;
use Serafim\Vulkan\Struct\VkImageSubresourceLayers;
use Serafim\Vulkan\Struct\VkImageSubresourceRange;
use Serafim\Vulkan\Struct\VkImageViewCreateInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Struct\VkMemoryAllocateInfo;
use Serafim\Vulkan\Struct\VkOffset2D;
use Serafim\Vulkan\Struct\VkOffset3D;
use Serafim\Vulkan\Struct\VkPipelineColorBlendAttachmentState;
use Serafim\Vulkan\Struct\VkPipelineColorBlendStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineDepthStencilStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineInputAssemblyStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineLayoutCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineMultisampleStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineRasterizationStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineShaderStageCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineVertexInputStateCreateInfo;
use Serafim\Vulkan\Struct\VkPipelineViewportStateCreateInfo;
use Serafim\Vulkan\Struct\VkPresentInfoKHR;
use Serafim\Vulkan\Struct\VkPushConstantRange;
use Serafim\Vulkan\Struct\VkRect2D;
use Serafim\Vulkan\Struct\VkRenderPassBeginInfo;
use Serafim\Vulkan\Struct\VkRenderPassCreateInfo;
use Serafim\Vulkan\Struct\VkSamplerCreateInfo;
use Serafim\Vulkan\Struct\VkSemaphoreCreateInfo;
use Serafim\Vulkan\Struct\VkShaderModuleCreateInfo;
use Serafim\Vulkan\Struct\VkStencilOpState;
use Serafim\Vulkan\Struct\VkSubmitInfo;
use Serafim\Vulkan\Struct\VkSubpassDependency;
use Serafim\Vulkan\Struct\VkSubpassDescription;
use Serafim\Vulkan\Struct\VkSwapchainCreateInfoKHR;
use Serafim\Vulkan\Struct\VkVertexInputAttributeDescription;
use Serafim\Vulkan\Struct\VkVertexInputBindingDescription;
use Serafim\Vulkan\Struct\VkViewport;
use Serafim\Vulkan\Struct\VkWin32SurfaceCreateInfoKHR;
use Serafim\Vulkan\Struct\VkWriteDescriptorSet;
use Serafim\Vulkan\Vulkan;

use const Serafim\Vulkan\VK_KHR_SWAPCHAIN_EXTENSION_NAME;
use const Serafim\Vulkan\VK_SUBPASS_EXTERNAL;

require __DIR__ . '/../vendor/autoload.php';

// Matrices are row-major arrays of 16 floats, `pack_matrix()` makes them column-major for GLSL.

/**
 * @param list<float> $a
 * @param list<float> $b
 * @return list<float>
 */
function multiply(array $a, array $b): array
{
    $result = [];

    for ($row = 0; $row < 4; ++$row) {
        for ($column = 0; $column < 4; ++$column) {
            $sum = 0.0;

            for ($i = 0; $i < 4; ++$i) {
                $sum += $a[$row * 4 + $i] * $b[$i * 4 + $column];
            }

            $result[] = $sum;
        }
    }

    return $result;
}

/**
 * @param list<float> $matrix
 */
function pack_matrix(array $matrix): string
{
    $columns = [];

    for ($column = 0; $column < 4; ++$column) {
        for ($row = 0; $row < 4; ++$row) {
            $columns[] = $matrix[$row * 4 + $column];
        }
    }

    return pack('f*', ...$columns);
}

/**
 * Vulkan clip space: y points down, depth is in 0..1.
 *
 * @return list<float>
 */
function perspective(float $fov, float $aspect, float $near, float $far): array
{
    $f = 1.0 / tan($fov / 2.0);

    return [
        $f / $aspect, 0.0, 0.0, 0.0,
        0.0, -$f, 0.0, 0.0,
        0.0, 0.0, $far / ($near - $far), $near * $far / ($near - $far),
        0.0, 0.0, -1.0, 0.0,
    ];
}

/**
 * @return list<float>
 */
function rotation_y(float $angle): array
{
    return [
        cos($angle), 0.0, sin($angle), 0.0,
        0.0, 1.0, 0.0, 0.0,
        -sin($angle), 0.0, cos($angle), 0.0,
        0.0, 0.0, 0.0, 1.0,
    ];
}

/**
 * @return list<float>
 */
function rotation_x(float $angle): array
{
    return [
        1.0, 0.0, 0.0, 0.0,
        0.0, cos($angle), -sin($angle), 0.0,
        0.0, sin($angle), cos($angle), 0.0,
        0.0, 0.0, 0.0, 1.0,
    ];
}

/**
 * @return list<float>
 */
function translation(float $x, float $y, float $z): array
{
    return [
        1.0, 0.0, 0.0, $x,
        0.0, 1.0, 0.0, $y,
        0.0, 0.0, 1.0, $z,
        0.0, 0.0, 0.0, 1.0,
    ];
}

$vulkan = Vulkan::load();
$kernel32 = FFI::cdef('void* GetModuleHandleW(void* name);', 'kernel32.dll');
// @see https://www.glfw.org/download
$glfw3 = FFI::cdef(file_get_contents(__DIR__ . '/glfw3.h'), __DIR__ . '/glfw3.dll');

$glfw3->glfwInit();
$glfw3->glfwWindowHint(0x00022001, 0x00);
$glfw3->glfwWindowHint(0x00020003, 0x00);

$window = $glfw3->glfwCreateWindow(800, 600, 'Cube', null, null);

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

$memoryProperties = $vulkan->vkGetPhysicalDeviceMemoryProperties($physical);

$findMemoryType = static function (int $typeBits, int $required) use ($memoryProperties): int {
    foreach ($memoryProperties->memoryTypes as $index => $type) {
        if (($typeBits & (1 << $index)) !== 0 && ($type->propertyFlags & $required) === $required) {
            return $index;
        }
    }

    throw new RuntimeException('No suitable memory type');
};

// Host visible buffer filled from a binary string
$createBuffer = static function (int $usage, string $data) use ($vulkan, $device, $findMemoryType): array {
    $buffer = $vulkan->vkCreateBuffer($device, new VkBufferCreateInfo(size: \strlen($data), usage: $usage));
    $requirements = $vulkan->vkGetBufferMemoryRequirements($device, $buffer);

    $memory = $vulkan->vkAllocateMemory($device, new VkMemoryAllocateInfo(
        allocationSize: $requirements->size,
        memoryTypeIndex: $findMemoryType(
            $requirements->memoryTypeBits,
            VkMemoryPropertyFlagBits::VK_MEMORY_PROPERTY_HOST_VISIBLE_BIT
            | VkMemoryPropertyFlagBits::VK_MEMORY_PROPERTY_HOST_COHERENT_BIT,
        ),
    ));
    $vulkan->vkBindBufferMemory($device, $buffer, $memory, 0);

    $pointer = $vulkan->vkMapMemory($device, $memory, 0, \strlen($data));
    FFI::memcpy($pointer, $data, \strlen($data));
    $vulkan->vkUnmapMemory($device, $memory);

    return [$buffer, $memory];
};

// Swapchain
$capabilities = $vulkan->vkGetPhysicalDeviceSurfaceCapabilitiesKHR($physical, $surface);
$extent = $capabilities->currentExtent;
$format = VkFormat::VK_FORMAT_B8G8R8A8_UNORM;
$depthFormat = VkFormat::VK_FORMAT_D32_SFLOAT;

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

// Depth buffer
$depthImage = $vulkan->vkCreateImage($device, new VkImageCreateInfo(
    extent: new VkExtent3D($extent->width, $extent->height, 1),
    imageType: VkImageType::VK_IMAGE_TYPE_2D,
    format: $depthFormat,
    mipLevels: 1,
    arrayLayers: 1,
    samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
    usage: VkImageUsageFlagBits::VK_IMAGE_USAGE_DEPTH_STENCIL_ATTACHMENT_BIT,
));
$depthRequirements = $vulkan->vkGetImageMemoryRequirements($device, $depthImage);
$depthMemory = $vulkan->vkAllocateMemory($device, new VkMemoryAllocateInfo(
    allocationSize: $depthRequirements->size,
    memoryTypeIndex: $findMemoryType($depthRequirements->memoryTypeBits, 0),
));
$vulkan->vkBindImageMemory($device, $depthImage, $depthMemory, 0);
$depthView = $vulkan->vkCreateImageView($device, new VkImageViewCreateInfo(
    image: $depthImage,
    components: new VkComponentMapping(),
    subresourceRange: new VkImageSubresourceRange(
        VkImageAspectFlagBits::VK_IMAGE_ASPECT_DEPTH_BIT,
        levelCount: 1,
        layerCount: 1,
    ),
    viewType: VkImageViewType::VK_IMAGE_VIEW_TYPE_2D,
    format: $depthFormat,
));

// Render pass and framebuffers
$renderPass = $vulkan->vkCreateRenderPass($device, new VkRenderPassCreateInfo(
    attachments: [
        new VkAttachmentDescription(
            format: $format,
            samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
            loadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_CLEAR,
            storeOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_STORE,
            stencilLoadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_DONT_CARE,
            stencilStoreOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_DONT_CARE,
            initialLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
            finalLayout: VkImageLayout::VK_IMAGE_LAYOUT_PRESENT_SRC_KHR,
        ),
        new VkAttachmentDescription(
            format: $depthFormat,
            samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
            loadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_CLEAR,
            storeOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_DONT_CARE,
            stencilLoadOp: VkAttachmentLoadOp::VK_ATTACHMENT_LOAD_OP_DONT_CARE,
            stencilStoreOp: VkAttachmentStoreOp::VK_ATTACHMENT_STORE_OP_DONT_CARE,
            initialLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
            finalLayout: VkImageLayout::VK_IMAGE_LAYOUT_DEPTH_STENCIL_ATTACHMENT_OPTIMAL,
        ),
    ],
    subpasses: [new VkSubpassDescription(
        pipelineBindPoint: VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS,
        colorAttachments: [new VkAttachmentReference(0, VkImageLayout::VK_IMAGE_LAYOUT_COLOR_ATTACHMENT_OPTIMAL)],
        depthStencilAttachment: new VkAttachmentReference(
            1,
            VkImageLayout::VK_IMAGE_LAYOUT_DEPTH_STENCIL_ATTACHMENT_OPTIMAL,
        ),
    )],
    dependencies: [new VkSubpassDependency(
        srcSubpass: VK_SUBPASS_EXTERNAL,
        dstSubpass: 0,
        srcStageMask: VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT
            | VkPipelineStageFlagBits::VK_PIPELINE_STAGE_EARLY_FRAGMENT_TESTS_BIT,
        dstStageMask: VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT
            | VkPipelineStageFlagBits::VK_PIPELINE_STAGE_EARLY_FRAGMENT_TESTS_BIT,
        dstAccessMask: VkAccessFlagBits::VK_ACCESS_COLOR_ATTACHMENT_WRITE_BIT
            | VkAccessFlagBits::VK_ACCESS_DEPTH_STENCIL_ATTACHMENT_WRITE_BIT,
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
        attachments: [$view, $depthView],
        width: $extent->width,
        height: $extent->height,
        layers: 1,
    ));
}

// Geometry: 24 vertices (position xyz + uv), 36 indices
$faces = [
    // normal axis, 4 corners
    [[-1, -1, 1], [1, -1, 1], [1, 1, 1], [-1, 1, 1]],
    [[1, -1, -1], [-1, -1, -1], [-1, 1, -1], [1, 1, -1]],
    [[1, -1, 1], [1, -1, -1], [1, 1, -1], [1, 1, 1]],
    [[-1, -1, -1], [-1, -1, 1], [-1, 1, 1], [-1, 1, -1]],
    [[-1, 1, 1], [1, 1, 1], [1, 1, -1], [-1, 1, -1]],
    [[-1, -1, -1], [1, -1, -1], [1, -1, 1], [-1, -1, 1]],
];
$uvs = [[0, 0], [1, 0], [1, 1], [0, 1]];

$vertices = [];
$indices = [];

foreach ($faces as $face => $corners) {
    foreach ($corners as $corner => $position) {
        array_push($vertices, ...$position, ...$uvs[$corner]);
    }

    array_push($indices, $face * 4, $face * 4 + 1, $face * 4 + 2, $face * 4, $face * 4 + 2, $face * 4 + 3);
}

[$vertexBuffer] = $createBuffer(
    VkBufferUsageFlagBits::VK_BUFFER_USAGE_VERTEX_BUFFER_BIT,
    pack('f*', ...$vertices),
);
[$indexBuffer] = $createBuffer(
    VkBufferUsageFlagBits::VK_BUFFER_USAGE_INDEX_BUFFER_BIT,
    pack('v*', ...$indices),
);

// Texture: 64x64 checkerboard uploaded through a staging buffer
$size = 64;
$pixels = '';

for ($y = 0; $y < $size; ++$y) {
    for ($x = 0; $x < $size; ++$x) {
        $pixels .= (($x >> 3) + ($y >> 3)) % 2 === 0
            ? "\xFF\x80\x20\xFF"
            : "\x20\x40\xC0\xFF";
    }
}

[$stagingBuffer] = $createBuffer(VkBufferUsageFlagBits::VK_BUFFER_USAGE_TRANSFER_SRC_BIT, $pixels);

$texture = $vulkan->vkCreateImage($device, new VkImageCreateInfo(
    extent: new VkExtent3D($size, $size, 1),
    imageType: VkImageType::VK_IMAGE_TYPE_2D,
    format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
    mipLevels: 1,
    arrayLayers: 1,
    samples: VkSampleCountFlagBits::VK_SAMPLE_COUNT_1_BIT,
    usage: VkImageUsageFlagBits::VK_IMAGE_USAGE_TRANSFER_DST_BIT | VkImageUsageFlagBits::VK_IMAGE_USAGE_SAMPLED_BIT,
));
$textureRequirements = $vulkan->vkGetImageMemoryRequirements($device, $texture);
$textureMemory = $vulkan->vkAllocateMemory($device, new VkMemoryAllocateInfo(
    allocationSize: $textureRequirements->size,
    memoryTypeIndex: $findMemoryType($textureRequirements->memoryTypeBits, 0),
));
$vulkan->vkBindImageMemory($device, $texture, $textureMemory, 0);

$colorRange = new VkImageSubresourceRange(VkImageAspectFlagBits::VK_IMAGE_ASPECT_COLOR_BIT, levelCount: 1, layerCount: 1);

$pool = $vulkan->vkCreateCommandPool($device, new VkCommandPoolCreateInfo(
    flags: VkCommandPoolCreateFlagBits::VK_COMMAND_POOL_CREATE_RESET_COMMAND_BUFFER_BIT,
    queueFamilyIndex: $family,
));

[$upload] = $vulkan->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
    commandPool: $pool,
    commandBufferCount: 1,
));

$vulkan->vkBeginCommandBuffer($upload, new VkCommandBufferBeginInfo());
$vulkan->vkCmdPipelineBarrier(
    $upload,
    [],
    [],
    [new VkImageMemoryBarrier(
        image: $texture,
        subresourceRange: $colorRange,
        dstAccessMask: VkAccessFlagBits::VK_ACCESS_TRANSFER_WRITE_BIT,
        oldLayout: VkImageLayout::VK_IMAGE_LAYOUT_UNDEFINED,
        newLayout: VkImageLayout::VK_IMAGE_LAYOUT_TRANSFER_DST_OPTIMAL,
        srcQueueFamilyIndex: 0xFFFFFFFF,
        dstQueueFamilyIndex: 0xFFFFFFFF,
    )],
    VkPipelineStageFlagBits::VK_PIPELINE_STAGE_TOP_OF_PIPE_BIT,
    VkPipelineStageFlagBits::VK_PIPELINE_STAGE_TRANSFER_BIT,
);
$vulkan->vkCmdCopyBufferToImage(
    $upload,
    $stagingBuffer,
    $texture,
    VkImageLayout::VK_IMAGE_LAYOUT_TRANSFER_DST_OPTIMAL,
    [new VkBufferImageCopy(
        imageSubresource: new VkImageSubresourceLayers(VkImageAspectFlagBits::VK_IMAGE_ASPECT_COLOR_BIT, 0, 0, 1),
        imageOffset: new VkOffset3D(),
        imageExtent: new VkExtent3D($size, $size, 1),
    )],
);
$vulkan->vkCmdPipelineBarrier(
    $upload,
    [],
    [],
    [new VkImageMemoryBarrier(
        image: $texture,
        subresourceRange: $colorRange,
        srcAccessMask: VkAccessFlagBits::VK_ACCESS_TRANSFER_WRITE_BIT,
        dstAccessMask: VkAccessFlagBits::VK_ACCESS_SHADER_READ_BIT,
        oldLayout: VkImageLayout::VK_IMAGE_LAYOUT_TRANSFER_DST_OPTIMAL,
        newLayout: VkImageLayout::VK_IMAGE_LAYOUT_SHADER_READ_ONLY_OPTIMAL,
        srcQueueFamilyIndex: 0xFFFFFFFF,
        dstQueueFamilyIndex: 0xFFFFFFFF,
    )],
    VkPipelineStageFlagBits::VK_PIPELINE_STAGE_TRANSFER_BIT,
    VkPipelineStageFlagBits::VK_PIPELINE_STAGE_FRAGMENT_SHADER_BIT,
);
$vulkan->vkEndCommandBuffer($upload);
$vulkan->vkQueueSubmit($queue, [new VkSubmitInfo(commandBuffers: [$upload])], null);
$vulkan->vkQueueWaitIdle($queue);

$textureView = $vulkan->vkCreateImageView($device, new VkImageViewCreateInfo(
    image: $texture,
    components: new VkComponentMapping(),
    subresourceRange: $colorRange,
    viewType: VkImageViewType::VK_IMAGE_VIEW_TYPE_2D,
    format: VkFormat::VK_FORMAT_R8G8B8A8_UNORM,
));
$sampler = $vulkan->vkCreateSampler($device, new VkSamplerCreateInfo(
    magFilter: VkFilter::VK_FILTER_NEAREST,
    minFilter: VkFilter::VK_FILTER_NEAREST,
));

// Descriptors
$setLayout = $vulkan->vkCreateDescriptorSetLayout($device, new VkDescriptorSetLayoutCreateInfo(
    bindings: [new VkDescriptorSetLayoutBinding(
        binding: 0,
        descriptorType: VkDescriptorType::VK_DESCRIPTOR_TYPE_COMBINED_IMAGE_SAMPLER,
        descriptorCount: 1,
        stageFlags: VkShaderStageFlagBits::VK_SHADER_STAGE_FRAGMENT_BIT,
    )],
));
$descriptorPool = $vulkan->vkCreateDescriptorPool($device, new VkDescriptorPoolCreateInfo(
    maxSets: 1,
    poolSizes: [new VkDescriptorPoolSize(VkDescriptorType::VK_DESCRIPTOR_TYPE_COMBINED_IMAGE_SAMPLER, 1)],
));
[$descriptorSet] = $vulkan->vkAllocateDescriptorSets($device, new VkDescriptorSetAllocateInfo(
    descriptorPool: $descriptorPool,
    setLayouts: [$setLayout],
));
$vulkan->vkUpdateDescriptorSets($device, [new VkWriteDescriptorSet(
    dstSet: $descriptorSet,
    descriptorType: VkDescriptorType::VK_DESCRIPTOR_TYPE_COMBINED_IMAGE_SAMPLER,
    imageInfo: [new VkDescriptorImageInfo(
        $sampler,
        $textureView,
        VkImageLayout::VK_IMAGE_LAYOUT_SHADER_READ_ONLY_OPTIMAL,
    )],
)], []);

// Pipeline
$vertex = $vulkan->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(
    code: file_get_contents(__DIR__ . '/shaders/cube.vert.spv'),
));
$fragment = $vulkan->vkCreateShaderModule($device, new VkShaderModuleCreateInfo(
    code: file_get_contents(__DIR__ . '/shaders/cube.frag.spv'),
));

$layout = $vulkan->vkCreatePipelineLayout($device, new VkPipelineLayoutCreateInfo(
    setLayouts: [$setLayout],
    pushConstantRanges: [new VkPushConstantRange(VkShaderStageFlagBits::VK_SHADER_STAGE_VERTEX_BIT, 0, 64)],
));

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
    vertexInputState: new VkPipelineVertexInputStateCreateInfo(
        vertexBindingDescriptions: [
            new VkVertexInputBindingDescription(0, 5 * 4, VkVertexInputRate::VK_VERTEX_INPUT_RATE_VERTEX),
        ],
        vertexAttributeDescriptions: [
            new VkVertexInputAttributeDescription(0, 0, VkFormat::VK_FORMAT_R32G32B32_SFLOAT, 0),
            new VkVertexInputAttributeDescription(1, 0, VkFormat::VK_FORMAT_R32G32_SFLOAT, 3 * 4),
        ],
    ),
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
    depthStencilState: new VkPipelineDepthStencilStateCreateInfo(
        front: new VkStencilOpState(),
        back: new VkStencilOpState(),
        depthTestEnable: true,
        depthWriteEnable: true,
        depthCompareOp: VkCompareOp::VK_COMPARE_OP_LESS,
    ),
    colorBlendState: new VkPipelineColorBlendStateCreateInfo(
        attachments: [new VkPipelineColorBlendAttachmentState(colorWriteMask: 0b1111)],
    ),
    layout: $layout,
    renderPass: $renderPass,
)])[0];

// Commands: one buffer per framebuffer, recorded every frame
$commands = $vulkan->vkAllocateCommandBuffers($device, new VkCommandBufferAllocateInfo(
    commandPool: $pool,
    commandBufferCount: \count($framebuffers),
));

$projection = perspective(M_PI / 3, $extent->width / $extent->height, 0.1, 100.0);
$view = translation(0.0, 0.0, -5.0);

// Synchronization
$imageAvailable = $vulkan->vkCreateSemaphore($device, new VkSemaphoreCreateInfo());
$renderFinished = $vulkan->vkCreateSemaphore($device, new VkSemaphoreCreateInfo());
$fence = $vulkan->vkCreateFence($device, new VkFenceCreateInfo(
    flags: VkFenceCreateFlagBits::VK_FENCE_CREATE_SIGNALED_BIT,
));

$start = microtime(true);

while (!$glfw3->glfwWindowShouldClose($window)) {
    $glfw3->glfwPollEvents();

    $time = microtime(true) - $start;
    $mvp = multiply($projection, multiply($view, multiply(rotation_x($time * 0.5), rotation_y($time))));

    $vulkan->vkWaitForFences($device, [$fence], true, -1);
    $vulkan->vkResetFences($device, [$fence]);

    $index = $vulkan->vkAcquireNextImageKHR($device, $swapchain, -1, $imageAvailable)->value;
    $command = $commands[$index];

    $vulkan->vkBeginCommandBuffer($command, new VkCommandBufferBeginInfo());
    $vulkan->vkCmdBeginRenderPass(
        $command,
        new VkRenderPassBeginInfo(
            renderPass: $renderPass,
            framebuffer: $framebuffers[$index],
            renderArea: new VkRect2D(new VkOffset2D(), new VkExtent2D($extent->width, $extent->height)),
            clearValues: [
                new VkClearValue(color: new VkClearColorValue(float32: [0.0, 0.0, 0.0, 1.0])),
                new VkClearValue(depthStencil: new VkClearDepthStencilValue(1.0, 0)),
            ],
        ),
        VkSubpassContents::VK_SUBPASS_CONTENTS_INLINE,
    );
    $vulkan->vkCmdBindPipeline($command, VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS, $pipeline);
    $vulkan->vkCmdBindDescriptorSets(
        $command,
        VkPipelineBindPoint::VK_PIPELINE_BIND_POINT_GRAPHICS,
        $layout,
        0,
        [$descriptorSet],
        [],
    );
    $vulkan->vkCmdPushConstants(
        $command,
        $layout,
        VkShaderStageFlagBits::VK_SHADER_STAGE_VERTEX_BIT,
        0,
        pack_matrix($mvp),
    );
    $vulkan->vkCmdBindVertexBuffers($command, 0, [$vertexBuffer], [0]);
    $vulkan->vkCmdBindIndexBuffer($command, 0, VkIndexType::VK_INDEX_TYPE_UINT16, $indexBuffer);
    $vulkan->vkCmdDrawIndexed($command, \count($indices), 1, 0, 0, 0);
    $vulkan->vkCmdEndRenderPass($command);
    $vulkan->vkEndCommandBuffer($command);

    $vulkan->vkQueueSubmit($queue, [new VkSubmitInfo(
        waitSemaphores: [$imageAvailable],
        waitDstStageMask: [VkPipelineStageFlagBits::VK_PIPELINE_STAGE_COLOR_ATTACHMENT_OUTPUT_BIT],
        commandBuffers: [$command],
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
