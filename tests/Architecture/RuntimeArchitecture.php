<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Loader\Headers\PreprocessorHeadersLoader;
use Serafim\Vulkan\Vulkan;

/**
 * Boundary between the code that runs in the applications and the
 * development tooling, whose packages are not installed there.
 */
final class RuntimeArchitecture
{
    #[TestRule]
    public function testRuntimeDoesNotDependOnCommandsAndConsole(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan'))
            ->excluding(Selector::inNamespace('Serafim\Vulkan\Command'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Serafim\Vulkan\Command'),
                Selector::inNamespace('Symfony\Component\Console'),
            );
    }

    #[TestRule]
    public function testRuntimeDependsOnPreprocessorOnlyThroughItsLoader(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan'))
            ->excluding(
                Selector::classname(PreprocessorHeadersLoader::class),
                Selector::inNamespace('Serafim\Vulkan\Command'),
            )
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('FFI\Preprocessor'));
    }

    #[TestRule]
    public function testEntryPointDependsOnlyOnLoaderAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Vulkan::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(Vulkan::class),
                Selector::classname('Serafim\Vulkan\Loader\VulkanLoader'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
            );
    }

    #[TestRule]
    public function testEntryPointIsFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Vulkan::class))
            ->shouldBeFinal();
    }
}
