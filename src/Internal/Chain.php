<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use FFI\CData;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Struct\Extension\BufferedStructureInterface;
use Serafim\Vulkan\Struct\Extension\ChainedStructureInterface;

/**
 * The structures that a command fills in the chain of a returned structure:
 * the native structures are linked by `pNext`, the objects are created from
 * them after the call.
 *
 * @template TStructure of ChainedStructureInterface
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class Chain
{
    /**
     * @param list<array{class-string<TStructure>, CData}> $natives
     */
    private function __construct(
        private array $natives,
    ) {}

    /**
     * Allocates the native structures and links them to the root.
     *
     * @template TCreated of ChainedStructureInterface
     *
     * @param CData $root the native structure whose `pNext` starts the chain
     * @param list<class-string<TCreated>> $classes the structures to fill
     * @param class-string $marker the interface that the structures implement
     *
     * @return self<TCreated>
     * @throws InvalidStructureException in case of a class cannot be chained to the root
     */
    public static function allocate(Allocator $memory, CData $root, array $classes, string $marker): self
    {
        $natives = [];

        foreach ($classes as $class) {
            if (!\is_a($class, $marker, true)) {
                throw InvalidStructureException::becauseStructureCannotBeChained($class, $marker);
            }

            $natives[] = [$class, $class::createNative($memory)];
        }

        $previous = $root;

        foreach ($natives as [, $native]) {
            $previous->pNext = $memory->ffi->cast('void*', \FFI::addr($native));
            $previous = $native;
        }

        return new self($natives);
    }

    /**
     * Allocates the chain of every element of a native array of structures.
     *
     * @template TCreated of ChainedStructureInterface
     *
     * @param CData $items the native array
     * @param CData $count the pointer to the native length of the array
     * @param int $structureType the `sType` of the elements
     * @param list<class-string<TCreated>> $classes the structures to fill in each element
     * @param class-string $marker the interface that the structures implement
     *
     * @return list<self<TCreated>> the chains by the indexes of the elements
     * @throws InvalidStructureException in case of a class cannot be chained to the element
     */
    public static function allocateEach(
        Allocator $memory,
        CData $items,
        CData $count,
        int $structureType,
        array $classes,
        string $marker,
    ): array {
        /** @var int<0, max> $length */
        $length = $count[0];
        $chains = [];

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $item */
            $item = $items[$i];

            $item->sType = $structureType;
            $chains[] = self::allocate($memory, $item, $classes, $marker);
        }

        return $chains;
    }

    /**
     * Allocates the arrays that the structures of the chain fill, for the
     * lengths the command has written.
     *
     * @return bool whether any array is allocated: the command has to be
     *         called again
     */
    public function allocateBuffers(Allocator $memory): bool
    {
        $isAllocated = false;

        foreach ($this->natives as [$class, $native]) {
            if (\is_a($class, BufferedStructureInterface::class, true) && $class::allocateBuffers($memory, $native)) {
                $isAllocated = true;
            }
        }

        return $isAllocated;
    }

    /**
     * Creates the structures from the native ones the command has filled.
     *
     * @return list<TStructure>
     */
    public function read(): array
    {
        $chain = [];

        foreach ($this->natives as [$class, $native]) {
            $chain[] = $class::createFromCData($native);
        }

        return $chain;
    }
}
