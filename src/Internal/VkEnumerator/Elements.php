<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\VkEnumerator;

use FFI\CData;
use Serafim\Vulkan\VulkanBase;

/**
 * The native array of the elements that a command fills and the length it
 * has written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Internal
 */
final readonly class Elements
{
    private function __construct() {}

    /**
     * Allocates the native array of the elements for the length the command
     * has written.
     *
     * @param non-empty-string $type C type of an element
     * @param int<1, max> $length
     * @param int|null $structureType the `sType` of the elements that the
     *        command requires to be set before it fills them
     */
    public static function allocate(VulkanBase $vk, string $type, int $length, ?int $structureType): CData
    {
        $items = $vk->memory->new($type . '[' . $length . ']');

        if ($structureType === null) {
            return $items;
        }

        for ($i = 0; $i < $length; ++$i) {
            /** @var CData $item */
            $item = $items[$i];

            $item->sType = $structureType;
        }

        return $items;
    }

    /**
     * @return int<0, max>
     */
    public static function readLength(CData $count): int
    {
        /** @var int<0, max> $length */
        $length = $count->cdata;

        return $length;
    }
}
