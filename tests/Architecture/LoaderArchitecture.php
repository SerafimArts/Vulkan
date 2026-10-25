<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\VulkanLoader;

/**
 * Aggregation of the library, headers and platform loading.
 */
final class LoaderArchitecture
{
    #[TestRule]
    public function testLoaderDependsOnlyOnLoadingAreasAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(VulkanLoader::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(VulkanLoader::class),
                Selector::classname(Environment::class),
                Selector::inNamespace('Serafim\Vulkan\Loader\Library'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Platform'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Headers'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::inNamespace('FFI\Env'),
            );
    }

    #[TestRule]
    public function testEnvironmentHasNoDependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Environment::class))
            ->canOnlyDependOn()
            ->classes(Selector::classname(Environment::class));
    }

    #[TestRule]
    public function testLoaderAndEnvironmentAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(VulkanLoader::class),
                Selector::classname(Environment::class),
            )
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testLoaderAndEnvironmentAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(VulkanLoader::class),
                Selector::classname(Environment::class),
            )
            ->shouldBeReadonly();
    }
}
