<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

/**
 * What the commands need to know of the returned structures.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Structures
{
    public function __construct(
        /**
         * The returned structures that others are chained to
         *
         * @var list<non-empty-string>
         */
        private array $roots = [],
        /**
         * The returned structures that hold handles
         *
         * @var list<non-empty-string>
         */
        private array $contextual = [],
        /**
         * The returned structures whose chains can hold structures with the
         * arrays Vulkan fills
         *
         * @var list<non-empty-string>
         */
        private array $buffered = [],
        /**
         * The returned structures whose arrays Vulkan fills
         *
         * @var list<non-empty-string>
         */
        private array $filled = [],
    ) {}

    public function hasArrays(string $name): bool
    {
        return \in_array($name, $this->filled, true);
    }

    public function hasBuffers(string $name): bool
    {
        return \in_array($name, $this->buffered, true);
    }

    public function isRoot(string $name): bool
    {
        return \in_array($name, $this->roots, true);
    }

    public function needsContext(string $name): bool
    {
        return \in_array($name, $this->contextual, true);
    }
}
