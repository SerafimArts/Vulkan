<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\DoubleCall;

/**
 * A command that is called more than once. It owns the memory it is called
 * with, {@see \Serafim\Vulkan\Internal\DoubleCall} drives it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
interface QueryInterface
{
    /**
     * Calls the command with the memory that is allocated by now and returns
     * its result.
     */
    public function call(): mixed;

    /**
     * Reads what the previous call has written and allocates the memory for
     * it. It is not called after a failed call.
     *
     * @return bool whether anything is allocated: the command has to be called
     *         again
     */
    public function allocate(): bool;

    /**
     * Forgets the allocated memory before the command is called again from
     * the beginning.
     */
    public function reset(): void;
}
