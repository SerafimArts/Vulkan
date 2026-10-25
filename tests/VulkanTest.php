<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Library\LibraryLocatorInterface;
use Serafim\Vulkan\Loader\VulkanLoader;
use Serafim\Vulkan\Vulkan;

#[CoversClass(Vulkan::class)]
final class VulkanTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testApiIsLoadedOnce(): void
    {
        try {
            $ffi = Vulkan::load();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }

        self::assertSame($ffi, Vulkan::load());
    }

    #[RunInSeparateProcess]
    public function testLoaderGivenAfterLoadingIsIgnored(): void
    {
        try {
            $ffi = Vulkan::load();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }

        $loader = VulkanLoader::createDefault()->withLibraryLocator(
            new class implements LibraryLocatorInterface {
                public function getLibrary(?Environment $environment = null): string
                {
                    throw new \LogicException('The loader must not be used');
                }
            },
        );

        self::assertSame($ffi, Vulkan::load($loader));
    }
}
