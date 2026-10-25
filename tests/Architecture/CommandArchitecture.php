<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Symfony\Component\Console\Command\Command;

/**
 * Console commands, the development tooling of the library.
 */
final class CommandArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Command';

    #[TestRule]
    public function testCommandsDependOnlyOnHeadersPlatformAndConsole(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::inNamespace('Serafim\Vulkan\Loader\Headers'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Platform'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::inNamespace('Symfony\Component\Console'),
            );
    }

    #[TestRule]
    public function testCommandsExtendConsoleCommand(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->shouldExtend()
            ->classes(Selector::classname(Command::class));
    }

    #[TestRule]
    public function testCommandsAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testCommandsAreNamedWithCommandSuffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->shouldBeNamed('/Command$/', true);
    }
}
