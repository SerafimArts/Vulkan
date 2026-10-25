<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader;

/**
 * Detector of the Vulkan shared library for the operating systems it
 * supports.
 *
 * @see LibraryLoader
 */
interface LibraryDriverInterface
{
    /**
     * Tells whether the driver is able to look for the library in the
     * operating system family.
     *
     * @param string $family operating system family, see
     *        {@see \PHP_OS_FAMILY}
     */
    public function supports(string $family): bool;

    /**
     * Returns the pathname of the library, or `null` if the library is not
     * installed.
     *
     * @return non-empty-string|null
     */
    public function resolve(): ?string;
}
