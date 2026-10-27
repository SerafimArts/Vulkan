<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Memory;

use FFI\CData;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Struct\InputStructure;

/**
 * The allocations of one native struct: everything it points to lives as long
 * as the struct does.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class Allocation
{
    public \FFI $ffi;

    public function __construct(
        private Allocator $allocator,
        /**
         * The allocated memory that keeps everything the struct points to
         * alive
         */
        private CData $owner,
    ) {
        $this->ffi = $allocator->ffi;
    }

    /**
     * Returns the `char*` pointer to the copy of the string.
     */
    public function toCString(string $value): CData
    {
        return $this->allocator->toCString($value, $this->owner);
    }

    /**
     * Returns the function pointer of the C type that calls the closure.
     *
     * @param non-empty-string $type C type of the function pointer
     */
    public function toCallback(string $type, \Closure $closure): CData
    {
        return $this->allocator->toCallback($type, $closure);
    }

    /**
     * Returns the `char**` pointer to the copies of the strings.
     *
     * @param list<string> $values
     */
    public function toCStringArray(array $values): CData
    {
        return $this->allocator->toCStringArray($values, $this->owner);
    }

    /**
     * Allocates the native array of the handles.
     *
     * @param non-empty-string $type C type of a handle
     * @param list<Handle> $handles
     */
    public function toHandleArray(string $type, array $handles): CData
    {
        return $this->allocator->toHandleArray($type, $handles, $this->owner);
    }

    /**
     * Allocates the native array of the numbers.
     *
     * @param non-empty-string $type C type of a number
     * @param list<int|float> $values
     */
    public function toNumberArray(string $type, array $values): CData
    {
        return $this->allocator->toNumberArray($type, $values, $this->owner);
    }

    /**
     * Writes the structure of the user to native memory.
     */
    public function toStructure(InputStructure $structure): CData
    {
        return $structure->toCData($this->allocator, $this->owner);
    }

    /**
     * Writes the structures of the user to a native array.
     *
     * @param non-empty-list<InputStructure> $structures the structures of one
     *        class
     */
    public function toStructureArray(array $structures): CData
    {
        return InputStructure::createArray($this->allocator, $structures, $this->owner);
    }
}
