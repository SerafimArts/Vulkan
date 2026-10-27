<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Loader\Headers;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Build\Command\BuildCommand\BitFieldsPacker;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Headers\HeadersLoader;
use Serafim\Vulkan\Loader\Headers\PreprocessorHeadersLoader;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(PreprocessorHeadersLoader::class)]
final class PreprocessorHeadersLoaderTest extends TestCase
{
    public function testHeadersContainOnlyExtensionsOfThePlatform(): void
    {
        $headers = new PreprocessorHeadersLoader()->getHeaders(Platform::WIN32);

        self::assertStringContainsString('vkCreateInstance', $headers);
        self::assertStringContainsString('vkCreateWin32SurfaceKHR', $headers);
        self::assertStringNotContainsString('vkCreateXcbSurfaceKHR', $headers);
    }

    public function testAdditionalDefineChangesHeaders(): void
    {
        $headers = new PreprocessorHeadersLoader()
            ->withDefine('VK_USE_PLATFORM_WAYLAND_KHR')
            ->getHeaders(Platform::WIN32);

        self::assertStringContainsString('vkCreateWin32SurfaceKHR', $headers);
        self::assertStringContainsString('vkCreateWaylandSurfaceKHR', $headers);
    }

    public function testCompiledHeadersMatchTheOnesOnDisk(): void
    {
        $compiled = new BitFieldsPacker()->pack(new PreprocessorHeadersLoader()->getHeaders(Platform::XCB));

        self::assertSame(new HeadersLoader()->getHeaders(Platform::XCB), $compiled);
    }

    public function testBrokenIncludeDirectoryThrowsInvalidHeadersException(): void
    {
        $this->expectException(InvalidHeadersException::class);

        new PreprocessorHeadersLoader(__DIR__ . '/missing')->getHeaders(Platform::WIN32);
    }
}
