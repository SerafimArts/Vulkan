<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Loader;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Exception\LoadingException;
use Serafim\Vulkan\Loader\Headers\HeadersLoaderInterface;
use Serafim\Vulkan\Loader\Library\LibraryLocatorInterface;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDetectorInterface;
use Serafim\Vulkan\Loader\VulkanLoader;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(VulkanLoader::class)]
final class VulkanLoaderTest extends TestCase
{
    public function testDefaultLoaderCreatesFfiOfCurrentEnvironment(): void
    {
        try {
            $ffi = VulkanLoader::createDefault()->load();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }

        self::assertInstanceOf(\FFI::class, $ffi);
    }

    public function testComponentsShareEnvironmentAndPlatform(): void
    {
        $platforms = new \ArrayObject();
        $loader = self::createLoader(self::createHeadersLoader('typedef int VkFake;', $platforms));

        try {
            $loader->load(new Environment('Plan9'));
            self::fail('The missing library has to fail the loading');
        } catch (LoadingException) {
            self::assertSame([Platform::SCREEN], $platforms->getArrayCopy());
        }
    }

    public function testBrokenHeadersThrowLoadingException(): void
    {
        $loader = self::createLoader(self::createHeadersLoader('this is not C'));

        $this->expectException(LoadingException::class);

        $loader->load(new Environment('Plan9'));
    }

    public function testWithersReturnNewInstances(): void
    {
        $loader = VulkanLoader::createDefault();
        $headers = self::createHeadersLoader('');

        self::assertNotSame($loader, $loader->withLibraryLocator(self::createLocator()));
        self::assertNotSame($loader, $loader->withPlatformDetector(self::createDetector()));
        self::assertNotSame($loader, $loader->withHeadersLoader($headers));
    }

    private static function createLoader(HeadersLoaderInterface $headers): VulkanLoader
    {
        return new VulkanLoader(
            library: self::createLocator(),
            platform: self::createDetector(),
            headers: $headers,
        );
    }

    /**
     * @param \ArrayObject<int, Platform> $platforms
     */
    private static function createHeadersLoader(
        string $headers,
        \ArrayObject $platforms = new \ArrayObject(),
    ): HeadersLoaderInterface {
        return new readonly class($headers, $platforms) implements HeadersLoaderInterface {
            /**
             * @param \ArrayObject<int, Platform> $platforms
             */
            public function __construct(
                private string $headers,
                private \ArrayObject $platforms,
            ) {}

            public function getHeaders(Platform $platform): string
            {
                $this->platforms->append($platform);

                return $this->headers;
            }
        };
    }

    private static function createLocator(): LibraryLocatorInterface
    {
        return new readonly class implements LibraryLocatorInterface {
            public function getLibrary(?Environment $environment = null): string
            {
                if ($environment?->family !== 'Plan9') {
                    throw new \LogicException('Unexpected environment');
                }

                return __DIR__ . '/missing-library';
            }
        };
    }

    private static function createDetector(): PlatformDetectorInterface
    {
        return new readonly class implements PlatformDetectorInterface {
            public function getPlatform(?Environment $environment = null): Platform
            {
                if ($environment?->family !== 'Plan9') {
                    throw new \LogicException('Unexpected environment');
                }

                return Platform::SCREEN;
            }
        };
    }
}
