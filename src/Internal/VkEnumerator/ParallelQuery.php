<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\VkEnumerator;

use FFI\CData;
use Serafim\Vulkan\Internal\DoubleCall\QueryInterface;
use Serafim\Vulkan\VulkanBase;

/**
 * A command that fills several arrays of the same length: the first call
 * writes the length, the next one fills the arrays.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Internal
 */
final class ParallelQuery implements QueryInterface
{
    /**
     * The native arrays by the names, {@see null} until they are allocated.
     *
     * @var array<non-empty-string, CData|null>
     */
    private array $items;

    private CData $count;

    private CData $pointer;

    public function __construct(
        private readonly VulkanBase $vk,
        /**
         * Calls the command with the pointer to the length and the native
         * arrays in the order of the given ones
         *
         * @var \Closure(CData, CData|null...): mixed
         */
        private readonly \Closure $call,
        /**
         * The C type of an element and its `sType` by the names of the arrays
         *
         * @var array<non-empty-string, array{non-empty-string, int}>
         */
        private readonly array $arrays,
    ) {
        $this->count = $vk->memory->new('uint32_t');
        $this->pointer = \FFI::addr($this->count);

        $this->reset();
    }

    public function call(): mixed
    {
        return ($this->call)($this->pointer, ...\array_values($this->items));
    }

    public function allocate(): bool
    {
        $length = $this->getLength();

        if ($length === 0 || \reset($this->items) !== null) {
            return false;
        }

        foreach ($this->arrays as $name => [$type, $structureType]) {
            $this->items[$name] = Elements::allocate($this->vk, $type, $length, $structureType);
        }

        return true;
    }

    public function reset(): void
    {
        $this->items = \array_map(static fn(array $array): ?CData => null, $this->arrays);
    }

    /**
     * Returns the native array, {@see null} if the command has written
     * nothing.
     *
     * @param non-empty-string $name
     */
    public function findItems(string $name): ?CData
    {
        return $this->items[$name] ?? null;
    }

    /**
     * @return int<0, max>
     */
    public function getLength(): int
    {
        return Elements::readLength($this->count);
    }
}
