<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Memory;

use FFI\CData;
use Serafim\Vulkan\Handle\Handle;

/**
 * Allocates native memory. The memory that is given an owner lives as long as
 * the owner does.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final class Allocator
{
    /**
     * Native memory by the object that keeps it alive.
     *
     * @var \WeakMap<CData, \ArrayObject<int, CData>>
     */
    private \WeakMap $retained;

    public function __construct(
        public readonly \FFI $ffi,
    ) {
        $this->retained = new \WeakMap();
    }

    /**
     * Allocates a zeroed value of the C type.
     *
     * @param CData|null $owner the object that keeps the value alive as long
     *        as it is alive itself
     */
    public function new(string $type, ?CData $owner = null): CData
    {
        $data = $this->ffi->new($type);

        if ($owner !== null) {
            $retained = $this->retained[$owner] ??= new \ArrayObject();
            $retained[] = $data;
        }

        return $data;
    }

    /**
     * Allocates the native array with the copy of the string.
     *
     * @param CData|null $owner the object that keeps the array alive
     */
    public function newString(string $value, ?CData $owner = null): CData
    {
        $size = \strlen($value) + 1;
        $data = $this->new('char[' . $size . ']', $owner);

        \FFI::memcpy($data, $value . "\0", $size);

        return $data;
    }

    /**
     * Returns the `char*` pointer to the copy of the string.
     */
    public function toCString(string $value, CData $owner): CData
    {
        return $this->ffi->cast('char*', $this->newString($value, $owner));
    }

    /**
     * Returns the `char**` pointer to the copies of the strings.
     *
     * @param list<string> $values
     */
    public function toCStringArray(array $values, CData $owner): CData
    {
        $array = $this->new('char*[' . \max(1, \count($values)) . ']', $owner);
        $pointer = $this->ffi->cast('char**', $array);

        foreach ($values as $i => $value) {
            // @phpstan-ignore-next-line the assignment changes the native array
            $array[$i] = $this->toCString($value, $owner);
        }

        return $pointer;
    }

    /**
     * Allocates the native array of the handles.
     *
     * @param non-empty-string $type C type of a handle
     * @param list<Handle> $handles
     * @param CData|null $owner the object that keeps the array alive
     */
    public function toHandleArray(string $type, array $handles, ?CData $owner = null): CData
    {
        $array = $this->new($type . '[' . \max(1, \count($handles)) . ']', $owner);

        foreach ($handles as $i => $handle) {
            // @phpstan-ignore-next-line the assignment changes the native array
            $array[$i] = $this->ffi->cast($type, $handle->pointer);
        }

        \assert($array instanceof CData);

        return $array;
    }

    /**
     * Allocates the native array of the numbers.
     *
     * @param non-empty-string $type C type of a number
     * @param list<int|float> $values
     * @param CData|null $owner the object that keeps the array alive
     */
    public function toNumberArray(string $type, array $values, ?CData $owner = null): CData
    {
        $array = $this->new($type . '[' . \max(1, \count($values)) . ']', $owner);

        foreach ($values as $i => $value) {
            // @phpstan-ignore-next-line the assignment changes the native array
            $array[$i] = $value;
        }

        \assert($array instanceof CData);

        return $array;
    }
}
