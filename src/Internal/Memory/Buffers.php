<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Memory;

use FFI\CData;

/**
 * Allocation of the arrays that Vulkan fills in the elements of a native
 * array, for the lengths the command has written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class Buffers
{
    private function __construct() {}

    /**
     * Allocates the arrays of every element.
     *
     * @param \Closure(CData, CData): bool $allocate allocates the arrays of
     *        an element and tells whether it did, it gets the element and the
     *        native array that keeps the memory alive
     * @param int<0, max> $length the number of the elements
     *
     * @return bool whether any array is allocated: the command has to be
     *         called again
     */
    public static function allocateEach(\Closure $allocate, CData $items, int $length): bool
    {
        $isAllocated = false;

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $item */
            $item = $items[$i];

            if ($allocate($item, $items)) {
                $isAllocated = true;
            }
        }

        return $isAllocated;
    }
}
