<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\LibraryDriver;

/**
 * Vulkan library of Windows.
 */
final class WindowsLibraryDriver extends FamilyLibraryDriver
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        protected readonly string $family = 'Windows',
        /**
         * @var non-empty-list<non-empty-string>
         */
        protected readonly array $names = ['vulkan.dll', 'vulkan-1.dll'],
    ) {}
}
