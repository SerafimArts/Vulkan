<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Platform\Platform;
use Serafim\Vulkan\Loader\Platform\PlatformDetector;
use Serafim\Vulkan\Loader\Platform\PlatformDetectorInterface;
use Serafim\Vulkan\Loader\Platform\PlatformDriverInterface;

/**
 * Detection of the window system platform.
 */
final class PlatformArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Loader\Platform';

    #[TestRule]
    public function testPlatformDependsOnlyOnEnvironmentAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::classname(Environment::class),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
            );
    }

    #[TestRule]
    public function testPlatformIsPlainEnumWithoutDependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Platform::class))
            ->canOnlyDependOn()
            ->classes(Selector::classname(Platform::class));
    }

    #[TestRule]
    public function testPlatformIsEnum(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Platform::class))
            ->shouldBeEnum();
    }

    #[TestRule]
    public function testDriversImplementDriverInterface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE . '\PlatformDriver'))
            ->shouldImplement()
            ->classes(Selector::classname(PlatformDriverInterface::class));
    }

    #[TestRule]
    public function testDetectorImplementsDetectorInterface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(PlatformDetector::class))
            ->shouldImplement()
            ->classes(Selector::classname(PlatformDetectorInterface::class));
    }

    #[TestRule]
    public function testClassesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::isInterface(), Selector::isEnum())
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testClassesAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::isInterface(), Selector::isEnum())
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
