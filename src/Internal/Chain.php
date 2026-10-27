<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use FFI\CData;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Struct\Extension\Chained;

/**
 * The structures that a command fills in the chain of a returned structure:
 * the native structures are linked by `pNext`, the objects are created from
 * them after the call.
 *
 * @template TStructure of Chained
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
     * @template TCreated of Chained
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
