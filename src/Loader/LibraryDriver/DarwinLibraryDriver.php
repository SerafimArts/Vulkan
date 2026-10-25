<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\LibraryDriver;

/**
 * Vulkan library of macOS (the Vulkan SDK or MoltenVK).
 */
final class DarwinLibraryDriver extends FamilyLibraryDriver
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        protected readonly string $family = 'Darwin',
        /**
         * @var non-empty-list<non-empty-string>
         */
        protected readonly array $names = ['libvulkan.dylib', 'libvulkan.1.dylib', 'libMoltenVK.dylib'],
    ) {}
}
