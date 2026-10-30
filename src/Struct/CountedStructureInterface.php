<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct;

use FFI\CData;

/**
 * Structure returned by Vulkan whose arrays have their lengths in another
 * structure, the one the first call of the command fills.
 */
interface CountedStructureInterface
{
    /**
     * Copies the fields of the native struct.
     *
     * @param CData $counts the native structure with the lengths of the arrays
     */
    public static function createFromCData(CData $data, CData $counts): self;
}
