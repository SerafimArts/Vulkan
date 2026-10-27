<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Symfony\Component\Console\Command\Command;

/**
 * Console commands, the development tooling of the library.
 */
final class CommandArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Build\Command';

    #[TestRule]
    public function testCommandsDependOnlyOnHeadersPlatformAndConsole(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::inNamespace('Serafim\Vulkan\Build\Generator'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Headers'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Platform'),
                Selector::inNamespace('Serafim\Vulkan\Loader\Exception'),
                Selector::inNamespace('Symfony\Component\Console'),
                Selector::inNamespace('Symfony\Component\HttpClient'),
                Selector::inNamespace('Symfony\Contracts\HttpClient'),
            );
    }

    #[TestRule]
    public function testCommandsExtendConsoleCommand(): Rule
    {
        return PHPat::rule()
            ->classes(self::selectCommands())
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
            ->classes(Selector::AllOf(
                Selector::inNamespace(self::NAMESPACE),
                Selector::extends(Command::class),
            ))
            ->shouldBeNamed('/Command$/', true);
    }

    #[TestRule]
    public function testHelpersAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE . '\Download'))
            ->shouldBeReadonly();
    }

    private static function selectCommands(): SelectorInterface
    {
        return Selector::classname('/^Serafim\\\\Vulkan\\\\Build\\\\Command\\\\[^\\\\]+Command$/', true);
    }
}
