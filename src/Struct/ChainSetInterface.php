<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use Serafim\Vulkan\Exception\StructureNotInChainException;

/**
 * The structures that a command has filled in the `pNext` chain of a returned
 * structure. A structure is asked by its class, the structure of a newer
 * version of Vulkan is also an instance of the class of the older one
 * (`VkPhysicalDeviceVulkan13Properties` is a `VkPhysicalDeviceVulkan12Properties`).
 *
 * @template-covariant TExtension of object
 *
 * @extends \Traversable<int, TExtension>
 * @extends \ArrayAccess<class-string, object>
 */
interface ChainSetInterface extends
    \Traversable,
    \ArrayAccess,
    \Countable
{
    /**
     * Returns the structure that is an instance of the class, {@see null} if
     * the command has not filled one.
     *
     * @template TChained of TExtension
     *
     * @param class-string<TChained> $class
     *
     * @return TChained|null
     */
    public function find(string $class): ?object;

    /**
     * Returns the structure that is an instance of the class.
     *
     * @template TChained of TExtension
     *
     * @param class-string<TChained> $class
     *
     * @return TChained
     * @throws StructureNotInChainException in case of the command has not filled the structure
     */
    public function get(string $class): object;

    /**
     * Whether the chain has the structure that is an instance of the class.
     *
     * @param class-string $class
     */
    public function has(string $class): bool;
}
