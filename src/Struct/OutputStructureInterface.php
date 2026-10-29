<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;

/**
 * Structure returned by Vulkan: an immutable snapshot of the data of its
 * owner. It has no lifecycle and is never converted back to native memory.
 */
interface OutputStructureInterface
{
    /**
     * Copies the fields of the native struct.
     */
    public static function createFromCData(CData $data): self;
}
