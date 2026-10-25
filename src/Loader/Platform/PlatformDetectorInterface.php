<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;

/**
 * Source of the window system platform the Vulkan API is loaded for.
 */
interface PlatformDetectorInterface
{
    /**
     * Returns the platform of the environment.
     *
     * @param Environment|null $environment {@see null} for the environment
     *        of the current process
     *
     * @throws InvalidPlatformException in case of the environment is not
     *         supported or matches no platform
     */
    public function getPlatform(?Environment $environment = null): Platform;
}
