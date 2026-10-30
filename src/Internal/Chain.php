<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use FFI\CData;
use Serafim\Vulkan\Exception\ApiVersionTooLowException;
use Serafim\Vulkan\Exception\InvalidStructure\StructureNotChainableException;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Struct\ChainSet;
use Serafim\Vulkan\Struct\Extension\BufferedStructureInterface;
use Serafim\Vulkan\Struct\Extension\ChainedStructureInterface;
use Serafim\Vulkan\Struct\Extension\VersionedStructureInterface;

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
    private function __construct(
        /**
         * Every native structure of the chain once
         *
         * @var list<array{class-string<TStructure>, CData}>
         */
        private array $natives,
        /**
         * The structures the user asked for with their native structures, the
         * ones of the previous versions follow
         *
         * @var list<array{class-string<TStructure>, non-empty-list<CData>}>
         */
        private array $requested,
    ) {}

    /**
     * Allocates the native structures and links them to the root.
     *
     * @template TCreated of ChainedStructureInterface
     *
     * @param CData $root the native structure whose `pNext` starts the chain
     * @param list<class-string<TCreated>> $classes the structures to fill
     * @param class-string $marker the interface that the structures implement
     * @param int|null $apiVersion the version of Vulkan the instance was created
     *        for, {@see null} if it is not known
     *
     * @return self<TCreated>
     * @throws StructureNotChainableException in case of a class cannot be chained to the root
     * @throws ApiVersionTooLowException in case of a structure belongs to a newer version of Vulkan
     */
    public static function allocate(
        Allocator $memory,
        CData $root,
        array $classes,
        string $marker,
        ?int $apiVersion = null,
    ): self {
        $created = [];
        $requested = [];

        foreach ($classes as $class) {
            if (!\is_a($class, $marker, true)) {
                throw StructureNotChainableException::becauseStructureCannotBeChained($class, $marker);
            }

            self::assertVersion($class, $apiVersion);

            $group = [];

            foreach ([$class, ...self::findAncestors($class)] as $member) {
                $created[$member] ??= $member::createNative($memory);
                $group[] = $created[$member];
            }

            $requested[] = [$class, $group];
        }

        $natives = [];
        $previous = $root;

        foreach ($created as $class => $native) {
            $natives[] = [$class, $native];
            $previous->pNext = $memory->ffi->cast('void*', \FFI::addr($native));
            $previous = $native;
        }

        /** @var self<TCreated> */
        return new self($natives, $requested);
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
     * @param int|null $apiVersion the version of Vulkan the instance was created
     *        for, {@see null} if it is not known
     *
     * @return list<self<TCreated>> the chains by the indexes of the elements
     * @throws StructureNotChainableException in case of a class cannot be chained to the element
     * @throws ApiVersionTooLowException in case of a structure belongs to a newer version of Vulkan
     */
    public static function allocateEach(
        Allocator $memory,
        CData $items,
        CData $count,
        int $structureType,
        array $classes,
        string $marker,
        ?int $apiVersion = null,
    ): array {
        /** @var int<0, max> $length */
        $length = $count[0];
        $chains = [];

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $item */
            $item = $items[$i];

            $item->sType = $structureType;
            $chains[] = self::allocate($memory, $item, $classes, $marker, $apiVersion);
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
     * @return ChainSet<TStructure>
     */
    public function read(): ChainSet
    {
        $structures = [];

        foreach ($this->requested as [$class, $natives]) {
            /** @var TStructure $structure */
            $structure = $class::createFromCData(...$natives);

            $structures[] = $structure;
        }

        return new ChainSet($structures);
    }

    /**
     * Makes sure the instance was created for the version of Vulkan the
     * structure belongs to.
     *
     * @param class-string $class
     *
     * @throws ApiVersionTooLowException in case of the instance is older
     */
    private static function assertVersion(string $class, ?int $apiVersion): void
    {
        if ($apiVersion === null || !\is_a($class, VersionedStructureInterface::class, true)) {
            return;
        }

        $required = $class::getVersion();

        if (($required >> 12) > ($apiVersion >> 12)) {
            throw ApiVersionTooLowException::becauseStructureNeedsNewerVersion($class, $required, $apiVersion);
        }
    }

    /**
     * Returns the structures of the previous versions of the structure.
     *
     * @param class-string $class
     *
     * @return list<class-string<ChainedStructureInterface>>
     */
    private static function findAncestors(string $class): array
    {
        if (!\is_a($class, VersionedStructureInterface::class, true)) {
            return [];
        }

        return $class::getAncestors();
    }
}
