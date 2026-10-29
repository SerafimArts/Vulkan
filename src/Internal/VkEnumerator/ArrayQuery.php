<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\VkEnumerator;

use FFI\CData;
use Serafim\Vulkan\Internal\DoubleCall\QueryInterface;
use Serafim\Vulkan\Internal\Memory\Buffers;
use Serafim\Vulkan\VulkanBase;

/**
 * A command that fills one array of unknown length: the first call writes the
 * length, the next one fills the array.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Internal
 */
final class ArrayQuery implements QueryInterface
{
    private ?CData $items = null;

    private CData $count;

    private CData $pointer;

    /**
     * @param non-empty-string $countType C type of the length
     */
    public function __construct(
        private readonly VulkanBase $vk,
        /**
         * Calls the command with the pointer to the length and the native array
         *
         * @var \Closure(CData, CData|null): mixed
         */
        private readonly \Closure $call,
        /**
         * C type of element
         *
         * @var non-empty-string
         */
        private readonly string $type,
        /**
         * The `sType` of the elements that the command requires to be set
         * before it fills them
         */
        private readonly ?int $structureType = null,
        string $countType = 'uint32_t',
        /**
         * Allocates the arrays of an element for the lengths the command has
         * written
         *
         * @var (\Closure(CData, CData): bool)|null
         */
        private readonly ?\Closure $buffered = null,
    ) {
        $this->count = $vk->memory->new($countType);
        $this->pointer = \FFI::addr($this->count);
    }

    public function call(): mixed
    {
        return ($this->call)($this->pointer, $this->items);
    }

    public function allocate(): bool
    {
        $length = $this->getLength();

        if ($this->items !== null) {
            return $this->buffered !== null && Buffers::allocateEach($this->buffered, $this->items, $length);
        }

        if ($length === 0) {
            return false;
        }

        $this->items = Elements::allocate($this->vk, $this->type, $length, $this->structureType);

        return true;
    }

    public function reset(): void
    {
        $this->items = null;
    }

    /**
     * Returns the native array, {@see null} if the command has written
     * nothing.
     */
    public function findItems(): ?CData
    {
        return $this->items;
    }

    /**
     * @return int<0, max>
     */
    public function getLength(): int
    {
        return Elements::readLength($this->count);
    }
}
