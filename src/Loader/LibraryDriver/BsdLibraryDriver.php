<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\LibraryDriver;

/**
 * Vulkan library of the BSD family.
 */
final class BsdLibraryDriver extends FamilyLibraryDriver
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        protected readonly string $family = 'BSD',
        /**
         * @var non-empty-list<non-empty-string>
         */
        protected readonly array $names = ['libvulkan.so', 'libvulkan.so.1'],
    ) {}
}
