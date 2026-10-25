<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform\PlatformDriver;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * Win32 platform of Windows.
 */
final readonly class WindowsPlatformDriver implements PlatformDriverInterface
{
    public function supports(Environment $environment): bool
    {
        return $environment->family === 'Windows';
    }

    public function resolve(Environment $environment): Platform
    {
        return Platform::WIN32;
    }
}
