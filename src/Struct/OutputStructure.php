<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;

/**
 * Structure returned by Vulkan: an immutable snapshot of the data of its
 * owner. It has no lifecycle and is never converted back to native memory.
 */
abstract readonly class OutputStructure
{
    /**
     * Copies the fields of the native struct.
     */
    abstract public static function createFromCData(CData $data): static;

    /**
     * Creates the structures from the elements of the native array.
     *
     * @template T
     *
     * @param int<0, max> $length
     * @param \Closure(CData): T $factory
     *
     * @return list<T>
     */
    final protected static function createStructureListFromCData(CData $array, int $length, \Closure $factory): array
    {
        $list = [];

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $element */
            $element = $array[$i];

            $list[] = $factory($element);
        }

        return $list;
    }

    /**
     * Copies the elements of the native array.
     *
     * @param int<0, max> $length
     *
     * @return list<int|float>
     */
    final protected static function createListFromCData(CData $array, int $length): array
    {
        $list = [];

        for ($i = 0; $i < $length; ++$i) {
            /** @var int|float $element */
            $element = $array[$i];

            $list[] = $element;
        }

        return $list;
    }
}
