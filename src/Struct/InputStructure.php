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
abstract class InputStructure
{
    /**
     * Name of the C type
     */
    protected const string NATIVE_TYPE = '';

    /**
     * Writes the fields to the allocated native struct.
     *
     * @param Allocation $memory the allocations that live as long as the
     *        struct does
     * @param CData $data the native struct, it may be a view of an element
     *        of an array or of a member of another struct
     */
    abstract protected function write(Allocation $memory, CData $data): void;

    /**
     * Allocates the native struct and writes the fields to it.
     *
     * @param CData|null $owner the struct that points to this one, the
     *        memory lives as long as it does
     *
     * @internal this is an internal library class, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    final public function toCData(Allocator $memory, ?CData $owner = null): CData
    {
        $data = $memory->new(static::NATIVE_TYPE, $owner);

        $this->write(new Allocation($memory, $data), $data);

        return $data;
    }

    /**
     * Allocates the native array of the structures and writes them to it.
     *
     * @param non-empty-list<self> $structures the structures of one class
     * @param CData|null $owner the struct that points to the array, the
     *        memory lives as long as it does
     *
     * @throws InvalidStructureException in case of the structures are of
     *         different classes
     *
     * @internal this is an internal library class, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    final public static function createArray(Allocator $memory, array $structures, ?CData $owner = null): CData
    {
        foreach ($structures as $structure) {
            if ($structure::class !== $structures[0]::class) {
                throw InvalidStructureException::becauseElementsAreOfDifferentTypes(
                    $structures[0]::class,
                    $structure::class,
                );
            }
        }

        $array = $memory->new($structures[0]::NATIVE_TYPE . '[' . \count($structures) . ']', $owner);
        $allocation = new Allocation($memory, $array);

        foreach ($structures as $i => $structure) {
            /** @var CData $element */
            $element = $array[$i];

            $structure->write($allocation, $element);
        }

        return $array;
    }
}
