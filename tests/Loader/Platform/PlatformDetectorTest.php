<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Loader\Platform;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDetector;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\DarwinPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\WaylandPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\WindowsPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriver\XlibPlatformDriver;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(PlatformDetector::class)]
#[CoversClass(Environment::class)]
#[CoversClass(WindowsPlatformDriver::class)]
#[CoversClass(DarwinPlatformDriver::class)]
#[CoversClass(WaylandPlatformDriver::class)]
#[CoversClass(XlibPlatformDriver::class)]
final class PlatformDetectorTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{Environment, Platform}>
     */
    public static function environmentPlatformDataProvider(): iterable
    {
        yield 'windows' => [new Environment('Windows'), Platform::WIN32];
        yield 'darwin' => [new Environment('Darwin'), Platform::MACOS];
        yield 'solaris' => [new Environment('Solaris'), Platform::XLIB];
        yield 'linux without session' => [new Environment('Linux'), Platform::XLIB];
        yield 'linux x11 session' => [
            new Environment('Linux', ['XDG_SESSION_TYPE' => 'x11']),
            Platform::XLIB,
        ];
        yield 'linux wayland session' => [
            new Environment('Linux', ['XDG_SESSION_TYPE' => 'wayland']),
            Platform::WAYLAND,
        ];
        yield 'linux wayland display' => [
            new Environment('Linux', ['WAYLAND_DISPLAY' => 'wayland-0']),
            Platform::WAYLAND,
        ];
        yield 'bsd wayland display' => [
            new Environment('BSD', ['WAYLAND_DISPLAY' => 'wayland-0']),
            Platform::WAYLAND,
        ];
        yield 'linux empty wayland display' => [
            new Environment('Linux', ['WAYLAND_DISPLAY' => '']),
            Platform::XLIB,
        ];
    }

    #[DataProvider('environmentPlatformDataProvider')]
    public function testEnvironmentIsDetectedAsPlatform(
        Environment $environment,
        Platform $expected,
    ): void {
        $detector = PlatformDetector::createDefault();

        self::assertSame($expected, $detector->getPlatform($environment));
    }

    public function testWaylandDriverDoesNotSupportWindows(): void
    {
        self::assertFalse(
            new WaylandPlatformDriver()->supports(new Environment('Windows')),
        );
    }

    public function testUnsupportedFamilyThrowsInvalidPlatformException(): void
    {
        $this->expectException(InvalidPlatformException::class);

        PlatformDetector::createDefault()->getPlatform(new Environment('Unknown'));
    }

    public function testAddedDriverOverridesDefaultOnes(): void
    {
        $detector = PlatformDetector::createDefault()
            ->withDriver(self::createDriver('Linux', Platform::XCB));

        self::assertSame(
            Platform::XCB,
            $detector->getPlatform(new Environment('Linux')),
        );
    }

    public function testAddedDriverSupportsNewFamily(): void
    {
        $detector = PlatformDetector::createDefault()
            ->withDriver(self::createDriver('Plan9', Platform::SCREEN));

        self::assertSame(
            Platform::SCREEN,
            $detector->getPlatform(new Environment('Plan9')),
        );
    }

    public function testNextDriverIsAskedWhenPreviousOneDetectsNothing(): void
    {
        $detector = new PlatformDetector([
            self::createDriver('Linux', null),
            self::createDriver('Linux', Platform::XLIB),
        ]);

        self::assertSame(
            Platform::XLIB,
            $detector->getPlatform(new Environment('Linux')),
        );
    }

    public function testUndetectedPlatformThrowsInvalidPlatformException(): void
    {
        $detector = new PlatformDetector([self::createDriver('Linux', null)]);

        $this->expectException(InvalidPlatformException::class);

        $detector->getPlatform(new Environment('Linux'));
    }

    private static function createDriver(
        string $family,
        ?Platform $platform,
    ): PlatformDriverInterface {
        return new readonly class($family, $platform) implements PlatformDriverInterface {
            public function __construct(
                private string $family,
                private ?Platform $platform,
            ) {}

            public function supports(Environment $environment): bool
            {
                return $environment->family === $this->family;
            }

            public function resolve(Environment $environment): ?Platform
            {
                return $this->platform;
            }
        };
    }
}
