<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Driver;

use Serafim\Vulkan\Exception\CommandNotAvailableException;
use Serafim\Vulkan\Handle\Handle;

/**
 * Where the procedures of the commands come from.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
interface DriverInterface
{
    /**
     * Returns the procedure of the command.
     *
     * @param non-empty-string $command
     * @param Handle|null $handle the handle the command is called on,
     *        {@see null} for a command that needs none
     *
     * @throws CommandNotAvailableException in case of the command is not
     *         supported
     */
    public function getProcedure(string $command, ?Handle $handle = null): callable;
}
