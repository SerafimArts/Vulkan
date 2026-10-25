<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform\PlatformDriver;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * Wayland platform of a desktop session that runs a Wayland compositor.
 */
final readonly class WaylandPlatformDriver implements PlatformDriverInterface
{
    public function supports(Environment $environment): bool
    {
        return $environment->family === 'Linux'
            || $environment->family === 'BSD';
    }

    public function resolve(Environment $environment): ?Platform
    {
        $isWayland = $environment->findVariable('XDG_SESSION_TYPE') === 'wayland'
            || $environment->findVariable('WAYLAND_DISPLAY') !== null;

        if ($isWayland) {
            return Platform::WAYLAND;
        }

        return null;
    }
}
