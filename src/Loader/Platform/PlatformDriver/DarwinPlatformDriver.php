<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform\PlatformDriver;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * macOS platform of Darwin: the surfaces are created by `VK_EXT_metal_surface`, which deprecates
 * `VK_MVK_macos_surface`.
 */
final readonly class DarwinPlatformDriver implements PlatformDriverInterface
{
    public function supports(Environment $environment): bool
    {
        return $environment->family === 'Darwin';
    }

    public function resolve(Environment $environment): Platform
    {
        return Platform::METAL;
    }
}
