![Vulkan logo](resources/logo.svg)

# PHP Vulkan Bindings

PHP bindings of the Vulkan API on top of `ext-ffi` for Linux (Xlib, XCB,
Wayland), Windows and macOS (MoltenVK).

## Requirements

- PHP ^8.4
- ext-ffi
- GPU and drivers supporting Vulkan API

## Installation

- `composer require serafim/vulkan`

## Usage

Creates an instance and prints the names of the GPUs.

```php
use Serafim\Vulkan\Struct\VkApplicationInfo;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Vulkan;

$vk = Vulkan::load();

$instance = $vk->vkCreateInstance(new VkInstanceCreateInfo(
    applicationInfo: new VkApplicationInfo(
        applicationName: 'Example',
    ),
));

foreach ($vk->vkEnumeratePhysicalDevices($instance) as $device) {
    $properties = $vk->vkGetPhysicalDeviceProperties($device);

    echo $properties->deviceName . "\n";
}
```

### Instance version and extensions

The highest API version and the instance extensions the installed Vulkan
supports. Needs no instance. Lists of unknown length are lazy collections.

```php
echo $vk->vkEnumerateInstanceVersion() . "\n";

foreach ($vk->vkEnumerateInstanceExtensionProperties() as $extension) {
    echo $extension->extensionName . "\n";
}
```

### API version

The Vulkan version the application targets. All the macros of Vulkan
(`VK_MAKE_API_VERSION` and others) are available as `vk_*` functions.

```php
use function Serafim\Vulkan\vk_make_api_version;

$info = new VkApplicationInfo(
    applicationName: 'Example',
    apiVersion: vk_make_api_version(0, 1, 3, 0),
);
```

### Queue families

Every queue belongs to a family with its own capabilities. Finds the families
that support graphics, flags are checked with a bit mask.

```php
use Serafim\Vulkan\Enum\VkQueueFlagBits;

foreach ($vk->vkGetPhysicalDeviceQueueFamilyProperties($device) as $index => $family) {
    if ($family->queueFlags & VkQueueFlagBits::VK_QUEUE_GRAPHICS_BIT) {
        echo "Graphics queue family: $index ($family->queueCount queues)\n";
    }
}
```

### Chained structures

Properties are split across extension structures linked by `pNext`. List the
classes you need and read each back by class.

```php
use Serafim\Vulkan\Struct\VkPhysicalDeviceVulkan12Properties;

$properties = $vk->vkGetPhysicalDeviceProperties2($device, [
    VkPhysicalDeviceVulkan12Properties::class,
]);

echo $properties->findChained(VkPhysicalDeviceVulkan12Properties::class)?->driverName;
```

### Logical device and queue

Opens a logical device on a GPU and gets its queue. There is no `destroy`: an
object is destroyed with its PHP handle, children first.

```php
use Serafim\Vulkan\Struct\VkDeviceCreateInfo;
use Serafim\Vulkan\Struct\VkDeviceQueueCreateInfo;

$logical = $vk->vkCreateDevice($device, new VkDeviceCreateInfo(
    queueCreateInfos: [
        new VkDeviceQueueCreateInfo(queueFamilyIndex: 0, queuePriorities: [1.0]),
    ],
));

$queue = $vk->vkGetDeviceQueue($logical, 0, 0);
$vk->vkQueueWaitIdle($queue);
```

### Errors

A failed command throws `CommandFailedException`, its code is the `VkResult`
value.

```php
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;

try {
    $vk->vkCreateInstance(new VkInstanceCreateInfo(
        enabledLayerNames: ['missing'],
    ));
} catch (CommandFailedException $e) {
    $result = VkResult::from($e->getCode());
    
    echo $result->name;
}
```

More [in `./examples/*.php`](examples/).
