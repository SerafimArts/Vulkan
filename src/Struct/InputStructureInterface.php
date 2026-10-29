<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Internal\Memory\Allocation;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * Structure of the user: a plain mutable object that is converted to native
 * memory only while a command uses it. The native memory belongs to the
 * structure that points to it and lives as long as that one.
 */
interface InputStructureInterface
{
    /**
     * Writes the fields to the allocated native struct.
     *
     * @param Allocation $memory the allocations that live as long as the
     *        struct does
     * @param CData $data the native struct, it may be a view of an element
     *        of an array or of a member of another struct
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public function write(Allocation $memory, CData $data): void;

    /**
     * Allocates the native struct and writes the fields to it.
     *
     * @param CData|null $owner the struct that points to this one, the
     *        memory lives as long as it does
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public function toCData(Allocator $memory, ?CData $owner = null): CData;

    /**
     * Allocates the native array of the structures and writes them to it.
     *
     * @param non-empty-list<InputStructureInterface> $structures the
     *        structures of the class the method is called on
     * @param CData|null $owner the struct that points to the array, the
     *        memory lives as long as it does
     *
     * @throws InvalidStructureException in case of the structures are of
     *         different classes
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public static function createArray(Allocator $memory, array $structures, ?CData $owner = null): CData;
}
