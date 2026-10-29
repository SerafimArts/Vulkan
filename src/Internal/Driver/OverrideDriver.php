<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Driver;

use Serafim\Vulkan\Handle\Handle;

/**
 * The procedure of one command is replaced, the others are the driver's.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class OverrideDriver implements DriverInterface
{
    /**
     * @param non-empty-string $command
     */
    public function __construct(
        private DriverInterface $driver,
        private string $command,
        /**
         * @var callable
         */
        private mixed $procedure,
    ) {}

    public function getProcedure(string $command, ?Handle $handle = null): callable
    {
        if ($command === $this->command && \is_callable($this->procedure)) {
            return $this->procedure;
        }

        return $this->driver->getProcedure($command, $handle);
    }
}
