<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Internal\Memory\Allocation;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * Writing of the fields to native memory for the structures that implement
 * {@see InputStructureInterface}. A structure declares `NATIVE_TYPE`, the name
 * of the C type.
 *
 * @phpstan-require-implements InputStructureInterface
 */
trait InputStructureTrait
{
    /**
     * Copies the structures that Vulkan has written to the native array.
     *
     * @template T
     *
     * @param \Closure(CData): T $factory
     *
     * @return list<T>
     */
    final protected static function createResultList(CData $array, \Closure $factory): array
    {
        $list = [];

        // @phpstan-ignore-next-line the native array is countable
        for ($i = 0, $length = \count($array); $i < $length; ++$i) {
            /** @var CData $element */
            $element = $array[$i];

            $list[] = $factory($element);
        }

        return $list;
    }

    final public function toCData(Allocator $memory, ?CData $owner = null): CData
    {
        // @phpstan-ignore-next-line the structure declares the constant
        $data = $memory->new(static::NATIVE_TYPE, $owner);

        $this->write(new Allocation($memory, $data), $data);

        return $data;
    }

    final public static function createArray(Allocator $memory, array $structures, ?CData $owner = null): CData
    {
        foreach ($structures as $structure) {
            if ($structure::class !== static::class) {
                throw InvalidStructureException::becauseElementsAreOfDifferentTypes(
                    static::class,
                    $structure::class,
                );
            }
        }

        // @phpstan-ignore-next-line the structure declares the constant
        $array = $memory->new(static::NATIVE_TYPE . '[' . \count($structures) . ']', $owner);
        $allocation = new Allocation($memory, $array);

        foreach ($structures as $i => $structure) {
            /** @var CData $element */
            $element = $array[$i];

            $structure->write($allocation, $element);
        }

        return $array;
    }
}
