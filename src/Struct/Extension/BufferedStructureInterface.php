<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct\Extension;

use FFI\CData;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * A structure of the chain whose arrays Vulkan fills: the first call of the
 * command writes the lengths, the arrays are allocated for them and the
 * command is called again.
 */
interface BufferedStructureInterface extends ChainedStructureInterface
{
    /**
     * Allocates the arrays for the lengths the command has written.
     *
     * @return bool whether any array is allocated: the command has to be
     *         called again
     */
    public static function allocateBuffers(Allocator $memory, CData $data): bool;
}
