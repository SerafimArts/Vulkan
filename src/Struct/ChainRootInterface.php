<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

/**
 * A returned structure that other structures are chained to through `pNext`
 * (`VkPhysicalDeviceProperties2`): the command fills the structures the user
 * asks for.
 *
 * @template TExtension of object the structures that can be chained to it
 */
interface ChainRootInterface
{
    /**
     * The structures the command has filled.
     *
     * @var ChainSetInterface<TExtension>
     */
    public ChainSetInterface $chain { get; }
}
