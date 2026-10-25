<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Library;

use Serafim\Vulkan\Loader\Environment;

/**
 * Search of the Vulkan shared library in the environments it supports.
 *
 * @see LibraryLocator
 */
interface LibraryDriverInterface
{
    /**
     * Tells whether the driver is able to look for the library in the
     * environment.
     */
    public function supports(Environment $environment): bool;

    /**
     * Returns the pathname of the library, or {@see null} if the library is not
     * installed.
     *
     * @return non-empty-string|null
     */
    public function resolve(Environment $environment): ?string;
}
