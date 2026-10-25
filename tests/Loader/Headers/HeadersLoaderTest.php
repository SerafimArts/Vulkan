<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Loader\Headers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Headers\HeadersLoader;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(HeadersLoader::class)]
final class HeadersLoaderTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{Platform}>
     */
    public static function platformDataProvider(): iterable
    {
        foreach (Platform::cases() as $platform) {
            yield \strtolower($platform->name) => [$platform];
        }
    }

    #[DataProvider('platformDataProvider')]
    public function testCompiledHeadersOfEveryPlatformExist(Platform $platform): void
    {
        $headers = new HeadersLoader()->getHeaders($platform);

        self::assertStringContainsString('vkCreateInstance', $headers);
    }

    public function testHeadersContainOnlyExtensionsOfThePlatform(): void
    {
        $headers = new HeadersLoader()->getHeaders(Platform::WIN32);

        self::assertStringContainsString('vkCreateWin32SurfaceKHR', $headers);
        self::assertStringNotContainsString('vkCreateXcbSurfaceKHR', $headers);
    }

    public function testHeadersFilenameIsLowercasedPlatformName(): void
    {
        self::assertSame('vulkan-xlib_xrandr.h', HeadersLoader::getFilename(Platform::XLIB_XRANDR));
    }

    public function testMissingHeadersThrowInvalidHeadersException(): void
    {
        $this->expectException(InvalidHeadersException::class);

        new HeadersLoader(__DIR__ . '/missing')->getHeaders(Platform::WIN32);
    }
}
