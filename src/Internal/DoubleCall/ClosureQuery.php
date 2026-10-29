<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\DoubleCall;

/**
 * A query made of closures, for the commands written by the generator.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class ClosureQuery implements QueryInterface
{
    public function __construct(
        /**
         * @var \Closure(): mixed
         */
        private \Closure $call,
        /**
         * @var \Closure(): bool
         */
        private \Closure $allocate,
        /**
         * @var (\Closure(): void)|null
         */
        private ?\Closure $reset = null,
    ) {}

    public function call(): mixed
    {
        return ($this->call)();
    }

    public function allocate(): bool
    {
        return ($this->allocate)();
    }

    public function reset(): void
    {
        if ($this->reset !== null) {
            ($this->reset)();
        }
    }
}
