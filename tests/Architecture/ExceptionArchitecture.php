<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Exception\VulkanException;
use Serafim\Vulkan\Loader\Exception\LoaderException;

/**
 * Failures of the loading, the branch of the library exceptions.
 */
final class ExceptionArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Loader\Exception';

    private const string STRUCTURE_NAMESPACE = 'Serafim\Vulkan\Exception\InvalidStructure';

    #[TestRule]
    public function testExceptionsDependOnlyOnTheirBranch(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::classname(VulkanException::class),
            );
    }

    #[TestRule]
    public function testBranchRootIsAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(LoaderException::class))
            ->shouldBeAbstract();
    }

    #[TestRule]
    public function testBranchRootExtendsLibraryRoot(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(LoaderException::class))
            ->shouldExtend()
            ->classes(Selector::classname(VulkanException::class));
    }

    #[TestRule]
    public function testLeavesExtendBranchRoot(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::classname(LoaderException::class))
            ->shouldExtend()
            ->classes(Selector::classname(LoaderException::class));
    }

    #[TestRule]
    public function testLeavesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->excluding(Selector::classname(LoaderException::class))
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testExceptionsAreNamedWithExceptionSuffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->shouldBeNamed('/Exception$/', true);
    }

    #[TestRule]
    public function testInvalidStructureRootIsAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(InvalidStructureException::class))
            ->shouldBeAbstract();
    }

    #[TestRule]
    public function testInvalidStructureLeavesExtendTheirRoot(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::STRUCTURE_NAMESPACE))
            ->shouldExtend()
            ->classes(Selector::classname(InvalidStructureException::class));
    }

    #[TestRule]
    public function testInvalidStructureLeavesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::STRUCTURE_NAMESPACE))
            ->shouldBeFinal();
    }

    #[TestRule]
    public function testInvalidStructureLeavesAreNamedWithExceptionSuffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::STRUCTURE_NAMESPACE))
            ->shouldBeNamed('/Exception$/', true);
    }
}