<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct\Extension;

use FFI\CData;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * A returned structure whose arrays Vulkan fills: the first call of the
 * command writes the lengths, the arrays are allocated for them and the
 * command is called again.
 */
interface BufferedStructureInterface
{
    /**
     * Allocates the arrays for the lengths the command has written.
     *
     * @param CData $data the native struct, it may be a view of an element
     *        of an array
     * @param CData|null $owner the object that keeps the arrays alive: the
     *        native struct itself when it is {@see null}, the native array
     *        when `$data` is a view of its element
     *
     * @return bool whether any array is allocated: the command has to be
     *         called again
     */
    public static function allocateBuffers(Allocator $memory, CData $data, ?CData $owner = null): bool;
}
