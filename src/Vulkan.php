<?php

declare(strict_types=1);

namespace Serafim\Vulkan;

use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;
use Serafim\Vulkan\Loader\Exception\InvalidRuntimeException;
use Serafim\Vulkan\Loader\Exception\LoadingException;
use Serafim\Vulkan\Loader\VulkanLoader;

/**
 * Entry point of the library that holds the {@see \FFI} instance of the
 * Vulkan API.
 */
final class Vulkan
{
    private static ?\FFI $ffi = null;

    /**
     * Returns the {@see \FFI} instance of the Vulkan API. The API is loaded by
     * the first call, the following ones return the same instance.
     *
     * @param VulkanLoader|null $loader loader of the first call, the default
     *        one if {@see null}, it is ignored by the following calls
     *
     * @throws LoadingException in case of the library cannot be bound to the
     *         headers
     * @throws InvalidRuntimeException in case of `ext-ffi` is not available
     * @throws InvalidLibraryException in case of the library cannot be found
     * @throws InvalidPlatformException in case of the platform cannot be
     *         detected
     * @throws InvalidHeadersException in case of the headers cannot be
     *         read
     */
    public static function load(?VulkanLoader $loader = null): \FFI
    {
        return self::$ffi ??= ($loader ?? VulkanLoader::createDefault())
            ->load();
    }
}
