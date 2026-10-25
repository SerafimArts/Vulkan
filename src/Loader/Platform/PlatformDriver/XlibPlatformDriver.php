<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform\PlatformDriver;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * Xlib platform of the systems that run the X Window System.
 */
final readonly class XlibPlatformDriver implements PlatformDriverInterface
{
    public function supports(Environment $environment): bool
    {
        return $environment->family === 'Linux'
            || $environment->family === 'BSD'
            || $environment->family === 'Solaris';
    }

    public function resolve(Environment $environment): Platform
    {
        return Platform::XLIB;
    }
}
