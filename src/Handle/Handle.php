<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Handle;

use FFI\CData;
use Serafim\Vulkan\Internal\HandleLifetime;
use Serafim\Vulkan\VulkanBase;

/**
 * Object of Vulkan that is identified by a handle. Vulkan owns it, the
 * wrapper destroys it together with itself and keeps the parent it was
 * created from alive until then.
 */
abstract readonly class Handle
{
    private HandleLifetime $lifetime;

    /**
     * @param list<mixed> $references the objects that the native object refers
     *        to (the structures the command was given, the views of a
     *        framebuffer): they live as long as the handle does
     */
    public function __construct(
        /** @phpstan-ignore-next-line phpstan false positive, required to keep the dependency in memory */
        private VulkanBase $vk,
        /**
         * Pointer to the native object
         */
        public CData $pointer,
        /**
         * The handle this one was created from
         */
        public ?self $parent = null,
        /**
         * Destroys the native object, {@see null} if Vulkan owns it through
         * the parent
         *
         * @var \Closure(self): void
         */
        private ?\Closure $release = null,
        array $references = [],
    ) {
        $this->lifetime = new HandleLifetime();

        foreach ($references as $reference) {
            $this->lifetime->retain($reference);
        }

        if ($parent !== null) {
            $parent->lifetime->adopt($this);
        }
    }

    /**
     * Keeps the object alive as long as the handle is: the native object
     * refers to it (the memory a buffer is bound to).
     *
     * @api
     */
    final public function retain(mixed $reference): void
    {
        $this->lifetime->retain($reference);
    }

    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Destroys the native object after the objects created from it. A handle
     * lives as long as its children do, except for the end of the process:
     * the objects are destroyed in the order of creation then.
     */
    private function destroy(): void
    {
        if ($this->lifetime->isReleased) {
            return;
        }

        $this->lifetime->isReleased = true;

        foreach (\array_reverse($this->lifetime->getChildren()) as $child) {
            $child->destroy();
        }

        if ($this->release !== null) {
            ($this->release)($this);
        }
    }
}
