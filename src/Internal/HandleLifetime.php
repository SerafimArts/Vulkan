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
    private \WeakMap $children;

    public function __construct()
    {
        $this->children = new \WeakMap();
    }

    public function adopt(Handle $child): void
    {
        $this->children[$child] = true;
    }

    /**
     * Returns the children in the order of the creation.
     *
     * @return list<Handle>
     */
    public function getChildren(): array
    {
        $children = [];

        foreach ($this->children as $child => $_) {
            $children[] = $child;
        }

        return $children;
    }
}
