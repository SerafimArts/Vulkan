<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\DarwinPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\WaylandPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\WindowsPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\XlibPlatformDriver;

/**
 * Detects the window system platform of the Vulkan API by asking the drivers
 * in order of their priority.
 */
final readonly class PlatformDetector implements PlatformDetectorInterface
{
    public function __construct(
        /**
         * Drivers in order of their priority
         *
         * @var list<PlatformDriverInterface>
         */
        private array $drivers,
    ) {}

    /**
     * Creates the detector with the drivers of all the platforms known to the
     * library. Wayland goes before Xlib, because a Wayland session also
     * provides the X Window System through XWayland.
     */
    public static function createDefault(): self
    {
        return new self([
            new WindowsPlatformDriver(),
            new DarwinPlatformDriver(),
            new WaylandPlatformDriver(),
            new XlibPlatformDriver(),
        ]);
    }

    /**
     * Returns the copy of the detector that asks the given driver before all
     * the others, which allows overriding the behaviour of a default driver
     * or to support another platform.
     *
     * @api
     */
    public function withDriver(PlatformDriverInterface $driver): self
    {
        return new self([$driver, ...$this->drivers]);
    }

    /**
     * Returns the platform detected by the first driver that supports the
     * environment and recognizes it.
     */
    public function getPlatform(?Environment $environment = null): Platform
    {
        $environment ??= Environment::createFromGlobals();
        $isSupported = false;

        foreach ($this->drivers as $driver) {
            if (!$driver->supports($environment)) {
                continue;
            }

            $isSupported = true;

            $platform = $driver->resolve($environment);

            if ($platform !== null) {
                return $platform;
            }
        }

        if ($isSupported) {
            throw InvalidPlatformException::becausePlatformNotDetected($environment->family);
        }

        throw InvalidPlatformException::becauseUnsupportedFamily($environment->family);
    }
}
