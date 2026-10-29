<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct\Extension;

use FFI\CData;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * A structure that a command can fill in the chain of a returned structure.
 */
interface ChainedStructureInterface
{
    /**
     * Allocates the native structure with its `sType`.
     */
    public static function createNative(Allocator $memory): CData;

    /**
     * Copies the fields of the native structure the command has filled.
     */
    public static function createFromCData(CData $data): self;
}
