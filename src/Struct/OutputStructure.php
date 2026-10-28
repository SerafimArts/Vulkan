<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Internal\EnumCase;
use Serafim\Vulkan\VulkanBase;

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
     * @param CData|null $array {@see null} if Vulkan has not filled the array
     * @param int<0, max> $length
     * @param \Closure(CData): T $factory
     *
     * @return list<T>
     */
    final protected static function createStructureListFromCData(?CData $array, int $length, \Closure $factory): array
    {
        $list = [];

        if ($array === null) {
            return $list;
        }

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $element */
            $element = $array[$i];

            $list[] = $factory($element);
        }

        return $list;
    }

    /**
     * Creates the enums from the elements of the native array.
     *
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     * @param CData|null $array {@see null} if Vulkan has not filled the array
     * @param int<0, max> $length
     *
     * @return list<T>
     */
    final protected static function createEnumerationListFromCData(string $enum, ?CData $array, int $length): array
    {
        $list = [];

        if ($array === null) {
            return $list;
        }

        for ($i = 0; $i < $length; ++$i) {
            /** @var int $value */
            $value = $array[$i];

            $list[] = EnumCase::createFromValue($enum, $value);
        }

        return $list;
    }

    /**
     * Copies the string that Vulkan has written.
     */
    final protected static function createStringFromCData(?CData $pointer): ?string
    {
        if ($pointer === null) {
            return null;
        }

        return \FFI::string($pointer);
    }

    /**
     * Creates the handle from the pointer that Vulkan has written.
     *
     * @template T of Handle
     *
     * @param class-string<T> $class
     *
     * @return T|null {@see null} if the pointer is not set
     */
    final protected static function createHandleFromCData(
        string $class,
        ?CData $pointer,
        VulkanBase $vk,
        ?Handle $parent,
    ): ?Handle {
        if ($pointer === null) {
            return null;
        }

        return new $class($vk, $pointer, $parent);
    }

    /**
     * Creates the handles from the elements of the native array.
     *
     * @template T of Handle
     *
     * @param class-string<T> $class
     * @param int<0, max> $length
     *
     * @return list<T>
     */
    final protected static function createHandleListFromCData(
        string $class,
        CData $array,
        int $length,
        VulkanBase $vk,
        ?Handle $parent,
    ): array {
        $list = [];

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $pointer */
            $pointer = $array[$i];

            $list[] = new $class($vk, $pointer, $parent);
        }

        return $list;
    }

    /**
     * Copies the elements of the native array.
     *
     * @param CData|null $array {@see null} if Vulkan has not filled the array
     * @param int<0, max> $length
     *
     * @return list<int|float>
     */
    final protected static function createListFromCData(?CData $array, int $length): array
    {
        $list = [];

        if ($array === null) {
            return $list;
        }

        for ($i = 0; $i < $length; ++$i) {
            /** @var int|float $element */
            $element = $array[$i];

            $list[] = $element;
        }

        return $list;
    }
}
