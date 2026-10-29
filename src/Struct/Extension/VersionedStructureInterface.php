<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Struct\Extension;

use FFI\CData;

/**
 * The structure of a version of Vulkan (`VkPhysicalDeviceVulkan13Features`)
 * extends the one of the previous version, so it holds the members of all the
 * versions up to its own. Vulkan fills a separate native struct for each of
 * them.
 */
interface VersionedStructureInterface extends ChainedStructureInterface
{
    /**
     * Returns the structures of the previous versions, the nearest first.
     *
     * @return non-empty-list<class-string<ChainedStructureInterface>>
     */
    public static function getAncestors(): array;

    /**
     * Copies the fields of the native structures the command has filled.
     *
     * @param CData $data the native structure of this version
     * @param CData ...$ancestors the native structures of the previous
     *        versions in the order of {@see getAncestors()}
     */
    public static function createFromCData(CData $data, CData ...$ancestors): self;
}
