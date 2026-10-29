<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Exception\CommandNotAvailableException;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Internal\DoubleCall;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Internal\Memory\Buffers;
use Serafim\Vulkan\Internal\VkEnumerator;
use Serafim\Vulkan\Loader\Headers\PreprocessorHeadersLoader;
use Serafim\Vulkan\Loader\VulkanLoader;
use Serafim\Vulkan\VulkanBase;

/**
 * Boundary between the code that runs in the applications and the
 * development tooling, whose packages are not installed there.
 */
final class RuntimeArchitecture
{
    #[TestRule]
    public function testRuntimeDoesNotDependOnBuildAndToolingPackages(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan'))
            ->excluding(Selector::inNamespace('Serafim\Vulkan\Build'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Serafim\Vulkan\Build'),
                Selector::inNamespace('Nette'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\Process'),
                Selector::inNamespace('Symfony\Component\HttpClient'),
                Selector::inNamespace('Symfony\Contracts\HttpClient'),
            );
    }

    #[TestRule]
    public function testRuntimeDependsOnPreprocessorOnlyThroughItsLoader(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan'))
            ->excluding(
                Selector::classname(PreprocessorHeadersLoader::class),
                Selector::inNamespace('Serafim\Vulkan\Build'),
            )
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('FFI\Preprocessor'));
    }

    #[TestRule]
    public function testEnumeratorDependsOnlyOnCommandsAndResults(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(VkEnumerator::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(VkEnumerator::class),
                Selector::classname(VulkanBase::class),
                Selector::classname(CommandFailedException::class),
                Selector::classname(DoubleCall::class),
                Selector::inNamespace('Serafim\Vulkan\Internal\VkEnumerator'),
            );
    }

    #[TestRule]
    public function testDoubleCallDependsOnlyOnCommandsAndResults(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(DoubleCall::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Serafim\Vulkan\Internal\DoubleCall'),
                Selector::classname(DoubleCall::class),
                Selector::classname(VulkanBase::class),
                Selector::classname(CommandFailedException::class),
                Selector::classname(VkResult::class),
            );
    }

    #[TestRule]
    public function testVkEnumeratorQueriesDependOnlyOnTheRuntime(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan\Internal\VkEnumerator'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Serafim\Vulkan\Internal\VkEnumerator'),
                Selector::inNamespace('Serafim\Vulkan\Internal\DoubleCall'),
                Selector::classname(Buffers::class),
                Selector::classname(VulkanBase::class),
            );
    }

    #[TestRule]
    public function testEnumeratorIsFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(VkEnumerator::class))
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testBaseOfTheCommandsDependsOnlyOnLoaderAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(VulkanBase::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(VulkanBase::class),
                Selector::classname(VulkanLoader::class),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::classname(CommandFailedException::class),
                Selector::classname(CommandNotAvailableException::class),
                Selector::classname(VkResult::class),
                Selector::classname(Handle::class),
                Selector::classname(Allocator::class),
                Selector::inNamespace('Serafim\Vulkan\Internal\Driver'),
            );
    }

    #[TestRule]
    public function testDriversDependOnlyOnCommandsAndHandles(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Serafim\Vulkan\Internal\Driver'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Serafim\Vulkan\Internal\Driver'),
                Selector::classname(CommandNotAvailableException::class),
                Selector::classname(Handle::class),
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
