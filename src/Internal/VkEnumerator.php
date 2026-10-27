<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use FFI\CData;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
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
         * @var \Closure(CData): TItem
         */
        private \Closure $factory,
    ) {}

    /**
     * Calls the command and wraps the native array it has filled.
     *
     * @template TCreated
     *
     * @param non-empty-string $command the last two arguments of the
     *        command, the length and the elements, are added to the given ones
     * @param callable $procedure the pointer to the command
     * @param list<mixed> $arguments the arguments of the command before them
     * @param non-empty-string $type C type of an element
     * @param \Closure(CData): TCreated $factory creates an element from the
     *        native one
     * @param int|null $structureType the `sType` of the elements that the
     *        command requires to be set before it fills them
     *
     * @return self<TCreated>
     * @throws CommandFailedException in case of the command fails
     */
    public static function create(
        VulkanBase $vk,
        string $command,
        callable $procedure,
        array $arguments,
        string $type,
        \Closure $factory,
        ?int $structureType = null,
    ): self {
        $count = $vk->memory->new('uint32_t');
        $pointer = \FFI::addr($count);

        do {
            $vk->assertSuccess($command, self::call($procedure, [...$arguments, $pointer, null]));

            $length = self::readLength($count);

            if ($length === 0) {
                return new self(null, 0, $factory);
            }

            $items = $vk->memory->new($type . '[' . $length . ']');

            if ($structureType !== null) {
                for ($i = 0; $i < $length; ++$i) {
                    /** @var CData $item */
                    $item = $items[$i];

                    $item->sType = $structureType;
                }
            }

            $result = self::call($procedure, [...$arguments, $pointer, $items]);
        } while ($result === VkResult::VK_INCOMPLETE->value);

        $vk->assertSuccess($command, $result);

        return new self($items, self::readLength($count), $factory);
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

            yield $i => ($this->factory)($item);
        }
    }

    /**
     * Calls the command, a command that returns nothing is successful.
     *
     * @param list<mixed> $arguments
     */
    private static function call(callable $procedure, array $arguments): int
    {
        $result = $procedure(...$arguments);

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
