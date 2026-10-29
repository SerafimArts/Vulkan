<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use FFI\CData;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Internal\Memory\Buffers;
use Serafim\Vulkan\VulkanBase;

/**
 * Elements returned by a command that fills an array of unknown length. The
 * elements are created from the native array when they are requested.
 *
 * @template TItem
 *
 * @implements \IteratorAggregate<int, TItem>
 */
final readonly class VkEnumerator implements \IteratorAggregate, \Countable
{
    private function __construct(
        /**
         * Native array of the elements, {@see null} if there are none
         */
        private ?CData $items,
        /**
         * @var int<0, max>
         */
        private int $length,
        /**
         * @var \Closure(CData, int<0, max>): TItem
         */
        private \Closure $factory,
    ) {}

    /**
     * Calls the command and wraps the native array it has filled.
     *
     * @template TCreated
     *
     * @param non-empty-string $command
     * @param \Closure(CData, CData|null): mixed $call calls the command with
     *        the pointer to the length and the native array
     * @param non-empty-string $type C type of an element
     * @param \Closure(CData, int<0, max>): TCreated $factory creates an element
     *        from the native one with its index
     * @param int|null $structureType the `sType` of the elements that the
     *        command requires to be set before it fills them
     * @param \Closure(CData, CData): bool|null $buffered allocates the arrays
     *        of an element for the lengths the command has written and tells
     *        whether it did, the native array keeps them alive: the command is
     *        called again after that
     *
     * @return self<TCreated>
     * @throws CommandFailedException in case of the command fails
     */
    public static function create(
        VulkanBase $vk,
        string $command,
        \Closure $call,
        string $type,
        \Closure $factory,
        ?int $structureType = null,
        ?\Closure $buffered = null,
    ): self {
        [$items, $length] = self::fill($vk, $command, $call, $type, $structureType, buffered: $buffered);

        return new self($items, $length, $factory);
    }

    /**
     * Calls the command and returns the numbers it has written to the native
     * array.
     *
     * @template TNumber of int|float
     * @template TCreated
     *
     * @param non-empty-string $command
     * @param \Closure(CData, CData|null): mixed $call calls the command with
     *        the pointer to the length and the native array
     * @param non-empty-string $type C type of an element
     * @param \Closure(TNumber): TCreated $factory creates an element from
     *        the number
     *
     * @return list<TCreated>
     * @throws CommandFailedException in case of the command fails
     */
    public static function createList(
        VulkanBase $vk,
        string $command,
        \Closure $call,
        string $type,
        \Closure $factory,
    ): array {
        [$items, $length] = self::fill($vk, $command, $call, $type, null);

        $list = [];

        if ($items === null) {
            return $list;
        }

        for ($i = 0; $i < $length; ++$i) {
            /** @var TNumber $value */
            $value = $items[$i];

            $list[] = $factory($value);
        }

        return $list;
    }

    /**
     * Calls the command and returns the bytes it has written, the length of
     * the bytes is `size_t`.
     *
     * @param non-empty-string $command
     * @param \Closure(CData, CData|null): mixed $call calls the command with
     *        the pointer to the size and the native bytes
     *
     * @throws CommandFailedException in case of the command fails
     */
    public static function createBinary(VulkanBase $vk, string $command, \Closure $call): string
    {
        [$items, $length] = self::fill($vk, $command, $call, 'uint8_t', null, 'size_t');

        if ($items === null) {
            return '';
        }

        return \FFI::string($items, $length);
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * @return \Generator<int, TItem>
     */
    public function getIterator(): \Generator
    {
        if ($this->items === null) {
            return;
        }

        for ($i = 0; $i < $this->length; ++$i) {
            /** @var CData $item */
            $item = $this->items[$i];

            yield $i => ($this->factory)($item, $i);
        }
    }

    /**
     * Calls the command until it fills the native array.
     *
     * @param non-empty-string $command
     * @param \Closure(CData, CData|null): mixed $call
     * @param non-empty-string $type
     * @param non-empty-string $countType C type of the length
     * @param \Closure(CData, CData): bool|null $buffered
     *
     * @return array{CData|null, int<0, max>}
     * @throws CommandFailedException
     */
    private static function fill(
        VulkanBase $vk,
        string $command,
        \Closure $call,
        string $type,
        ?int $structureType,
        string $countType = 'uint32_t',
        ?\Closure $buffered = null,
    ): array {
        $count = $vk->memory->new($countType);
        $pointer = \FFI::addr($count);

        do {
            $vk->assertSuccess($command, self::getCode($call($pointer, null)));

            $length = self::readLength($count);

            if ($length === 0) {
                return [null, 0];
            }

            $items = $vk->memory->new($type . '[' . $length . ']');

            if ($structureType !== null) {
                for ($i = 0; $i < $length; ++$i) {
                    /** @var CData $item */
                    $item = $items[$i];

                    $item->sType = $structureType;
                }
            }

            $result = self::getCode($call($pointer, $items));

            while ($buffered !== null && Buffers::allocateEach($buffered, $items, $length)) {
                $result = self::getCode($call($pointer, $items));
            }
        } while ($result === VkResult::VK_INCOMPLETE->value);

        $vk->assertSuccess($command, $result);

        return [$items, self::readLength($count)];
    }

    /**
     * Returns the code the command has returned, a command that returns
     * nothing is successful.
     */
    private static function getCode(mixed $result): int
    {
        if (\is_int($result)) {
            return $result;
        }

        return 0;
    }

    /**
     * @return int<0, max>
     */
    private static function readLength(CData $count): int
    {
        /** @var int<0, max> $length */
        $length = $count->cdata;

        return $length;
    }
}
