<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform\PlatformDriver;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * macOS platform of Darwin.
 */
final readonly class DarwinPlatformDriver implements PlatformDriverInterface
{
    public function supports(Environment $environment): bool
    {
        return $environment->family === 'Darwin';
    }

    public function resolve(Environment $environment): Platform
    {
        return Platform::MACOS;
    }
}
