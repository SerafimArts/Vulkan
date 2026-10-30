<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;

/**
 * The version of Vulkan an instance was created for: the objects created from
 * it work in this version, whatever the device supports.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class ApiVersion
{
    private function __construct() {}

    /**
     * Returns the version of Vulkan the instance of the handle was created
     * for, {@see null} if the instance is not known.
     *
     * @return int<0, max>|null
     */
    public static function find(Handle $handle): ?int
    {
        $root = $handle;

        while ($root->parent !== null) {
            $root = $root->parent;
        }

        $info = $root->findReference(VkInstanceCreateInfo::class);

        if ($info === null) {
            return null;
        }

        if ($info->applicationInfo === null) {
            return 0;
        }

        return $info->applicationInfo->apiVersion;
    }
}
