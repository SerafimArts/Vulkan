<?php

declare(strict_types=1);

use Serafim\Vulkan\Struct\VkBufferCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Vulkan;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * The handles that live up to the end of the process are destroyed by the
 * engine in the order of the creation, the parents first.
 */
final class Holder
{
    /**
     * @var list<object>
     */
    public static array $objects = [];
}

$vk = Vulkan::load();
$instance = $vk->vkCreateInstance(new VkInstanceCreateInfo());
$devices = \iterator_to_array($vk->vkEnumeratePhysicalDevices($instance), false);

if ($devices === []) {
    exit(3);
}

$device = $vk->vkCreateDevice($devices[0], new VkDeviceCreateInfo(
    queueCreateInfos: [new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0])],
));
$buffer = $vk->vkCreateBuffer($device, new VkBufferCreateInfo(size: 4096, usage: 1));

Holder::$objects = [$instance, $device, $buffer];
