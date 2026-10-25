<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Loader\Headers\HeadersLoader;
use Serafim\Vulkan\Loader\Headers\HeadersLoaderInterface;
use Serafim\Vulkan\Loader\Headers\PreprocessorHeadersLoader;

/**
 * Source of the C declarations of the Vulkan API.
 */
final class HeadersArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Loader\Headers';

    #[TestRule]
    public function testHeadersDependOnlyOnPlatformPreprocessorAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::inNamespace('Serafim\Vulkan\Loader\Platform'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::inNamespace('FFI\Preprocessor'),
            );
    }

    #[TestRule]
    public function testOnlyPreprocessorLoaderDependsOnPreprocessor(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::classname(PreprocessorHeadersLoader::class))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('FFI\Preprocessor'));
    }

    #[TestRule]
    public function testLoadersImplementLoaderInterface(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(HeadersLoader::class),
                Selector::classname(PreprocessorHeadersLoader::class),
            )
            ->shouldImplement()
            ->classes(Selector::classname(HeadersLoaderInterface::class));
    }

    #[TestRule]
    public function testClassesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::isInterface())
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testClassesAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::isInterface())
            ->shouldBeReadonly();
    }

    #[TestRule]
    public function testInterfacesAreNamedWithInterfaceSuffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::inNamespace(self::NAMESPACE),
                Selector::isInterface(),
            ))
            ->shouldBeNamed('/Interface$/', true);
    }
}
