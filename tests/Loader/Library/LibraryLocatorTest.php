<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Loader\Library;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Library\LibraryDriver\FamilyLibraryDriver;
use Serafim\Vulkan\Loader\Library\LibraryDriverInterface;
use Serafim\Vulkan\Loader\Library\LibraryLocator;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(LibraryLocator::class)]
#[CoversClass(FamilyLibraryDriver::class)]
final class LibraryLocatorTest extends TestCase
{
    public function testFamilyDriverSupportsOnlyItsFamily(): void
    {
        $driver = new FamilyLibraryDriver('Linux', ['libvulkan.so']);

        self::assertTrue($driver->supports(new Environment('Linux')));
        self::assertFalse($driver->supports(new Environment('Windows')));
    }

    public function testUnsupportedFamilyThrowsInvalidLibraryException(): void
    {
        $this->expectException(InvalidLibraryException::class);

        LibraryLocator::createDefault()->getLibrary(new Environment('Unknown'));
    }

    public function testAddedDriverSupportsNewFamily(): void
    {
        $locator = LibraryLocator::createDefault()
            ->withDriver(self::createDriver('Plan9', '/usr/lib/libvulkan.a'));

        self::assertSame(
            '/usr/lib/libvulkan.a',
            $locator->getLibrary(new Environment('Plan9')),
        );
    }

    public function testAddedDriverOverridesDefaultOne(): void
    {
        $locator = LibraryLocator::createDefault()
            ->withDriver(self::createDriver('Linux', '/custom/libvulkan.so'));

        self::assertSame(
            '/custom/libvulkan.so',
            $locator->getLibrary(new Environment('Linux')),
        );
    }

    public function testNextDriverIsAskedWhenPreviousOneFindsNothing(): void
    {
        $locator = new LibraryLocator([
            self::createDriver('Linux', null),
            self::createDriver('Linux', '/fallback/libvulkan.so'),
        ]);

        self::assertSame(
            '/fallback/libvulkan.so',
            $locator->getLibrary(new Environment('Linux')),
        );
    }

    public function testMissingLibraryThrowsInvalidLibraryException(): void
    {
        $locator = new LibraryLocator([self::createDriver('Linux', null)]);

        $this->expectException(InvalidLibraryException::class);

        $locator->getLibrary(new Environment('Linux'));
    }

    public function testCurrentEnvironmentResolvesLibraryOrReportsItMissing(): void
    {
        try {
            $library = LibraryLocator::createDefault()->getLibrary();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }

        self::assertFileExists($library);
    }

    /**
     * @param non-empty-string|null $pathname
     */
    private static function createDriver(string $family, ?string $pathname): LibraryDriverInterface
    {
        return new readonly class($family, $pathname) implements LibraryDriverInterface {
            public function __construct(
                private string $family,
                private ?string $pathname,
            ) {}

            public function supports(Environment $environment): bool
            {
                return $environment->family === $this->family;
            }

            public function resolve(Environment $environment): ?string
            {
                return $this->pathname;
            }
        };
    }
}
