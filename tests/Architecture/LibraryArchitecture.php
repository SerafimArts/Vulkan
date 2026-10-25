<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Library\LibraryDriverInterface;
use Serafim\Vulkan\Loader\Library\LibraryLocator;
use Serafim\Vulkan\Loader\Library\LibraryLocatorInterface;

/**
 * Search of the Vulkan shared library.
 */
final class LibraryArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Loader\Library';

    #[TestRule]
    public function testLibraryDependsOnlyOnEnvironmentLocationAndExceptions(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::classname(Environment::class),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::inNamespace('FFI\Location'),
            );
    }

    #[TestRule]
    public function testDriversImplementDriverInterface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE . '\LibraryDriver'))
            ->shouldImplement()
            ->classes(Selector::classname(LibraryDriverInterface::class));
    }

    #[TestRule]
    public function testLocatorImplementsLocatorInterface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(LibraryLocator::class))
            ->shouldImplement()
            ->classes(Selector::classname(LibraryLocatorInterface::class));
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
