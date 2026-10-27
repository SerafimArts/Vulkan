<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use Serafim\Vulkan\Handle\Handle;

/**
 * What a handle shares with the objects created from it: they are destroyed
 * together, the children first.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final class HandleLifetime
{
    /**
     * Whether the native object is destroyed already.
     */
    public bool $isReleased = false;

    /**
     * The handles created from the handle that are alive.
     *
     * @var \WeakMap<Handle, true>
     */
    public readonly \WeakMap $children;

    public function __construct()
    {
        /** @var \WeakMap<Handle, true> $children */
        $children = new \WeakMap();

        $this->children = $children;
    }
}
