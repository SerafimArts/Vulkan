<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Build\Generator\Exception\GeneratorException;
use Serafim\Vulkan\CommandResult;
use Serafim\Vulkan\Internal\EnumCase;
use Serafim\Vulkan\VulkanBase;

/**
 * Generator of the code of the Vulkan API, the development tooling.
 */
final class GeneratorArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Build\Generator';

    #[TestRule]
    public function testGeneratorDependsOnlyOnCodeGeneratorAndTheClassesItWrites(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::inNamespace('Nette\PhpGenerator'),
                Selector::inNamespace('Symfony\Component\Process'),
                Selector::inNamespace('Serafim\Vulkan\Exception'),
                Selector::inNamespace('Serafim\Vulkan\Enum'),
                Selector::inNamespace('Serafim\Vulkan\Struct'),
                Selector::inNamespace('Serafim\Vulkan\Handle'),
                Selector::inNamespace('Serafim\Vulkan\Internal\Memory'),
                Selector::classname(VulkanBase::class),
                Selector::classname(CommandResult::class),
                Selector::classname(EnumCase::class),
            );
    }

    #[TestRule]
    public function testClassesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(
                Selector::isInterface(),
                Selector::isEnum(),
                Selector::classname(GeneratorException::class),
            )
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testExceptionsExtendGeneratorException(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE . '\Exception'))
            ->excluding(Selector::classname(GeneratorException::class))
            ->shouldExtend()
            ->classes(Selector::classname(GeneratorException::class));
    }
}
