<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use Serafim\Vulkan\Exception\StructureNotInChainException;

/**
 * @template-covariant TExtension of object
 *
 * @implements ChainSetInterface<TExtension>
 * @implements \IteratorAggregate<int, TExtension>
 */
final readonly class ChainSet implements
    ChainSetInterface,
    \IteratorAggregate
{
    public function __construct(
        /**
         * The structures in the order of the chain
         *
         * @var list<TExtension>
         */
        private array $structures = [],
    ) {}

    /**
     * @return \Traversable<int, TExtension>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->structures);
    }

    public function count(): int
    {
        return \count($this->structures);
    }

    public function find(string $class): ?object
    {
        foreach ($this->structures as $structure) {
            if ($structure instanceof $class) {
                return $structure;
            }
        }

        return null;
    }

    public function get(string $class): object
    {
        return $this->find($class)
            ?? throw StructureNotInChainException::becauseStructureIsNotInChain($class);
    }

    public function has(string $class): bool
    {
        foreach ($this->structures as $structure) {
            if ($structure instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Same as {@see has()}.
     *
     * @param class-string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        \assert(\is_string($offset));

        return $this->has($offset);
    }

    /**
     * Same as {@see get()}.
     *
     * @template TArgChained of TExtension
     *
     * @param class-string<TArgChained> $offset
     *
     * @return TArgChained
     * @throws StructureNotInChainException in case of the command has not
     *         filled the structure
     */
    public function offsetGet(mixed $offset): object
    {
        \assert(\is_string($offset));

        return $this->get($offset);
    }

    /**
     * @throws \BadMethodCallException
     */
    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException('The chain of a returned structure is read-only');
    }

    /**
     * @throws \BadMethodCallException
     */
    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('The chain of a returned structure is read-only');
    }
}
