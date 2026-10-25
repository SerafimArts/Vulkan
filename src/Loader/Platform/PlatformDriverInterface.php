<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform;

use Serafim\Vulkan\Loader\Environment;

/**
 * Detection of the window system platform of the Vulkan API in the
 * environments it supports.
 *
 * @see PlatformDetector
 */
interface PlatformDriverInterface
{
    /**
     * Tells whether the driver is able to detect the platform in the
     * environment.
     */
    public function supports(Environment $environment): bool;

    /**
     * Returns the detected platform, or {@see null} if the environment does not
     * belong to the platform of the driver.
     */
    public function resolve(Environment $environment): ?Platform;
}
