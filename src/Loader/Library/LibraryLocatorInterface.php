<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Library;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;

/**
 * Source of the pathname of the Vulkan shared library.
 */
interface LibraryLocatorInterface
{
    /**
     * Returns the pathname of the library.
     *
     * @param Environment|null $environment {@see null} for the environment
     *        of the current process
     *
     * @return non-empty-string
     * @throws InvalidLibraryException in case of the environment is not
     *         supported or the library is not installed
     */
    public function getLibrary(?Environment $environment = null): string;
}
