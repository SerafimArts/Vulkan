<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Internal\EnumCase;
use Serafim\Vulkan\Struct\InputStructureInterface;
use Serafim\Vulkan\Struct\InputStructureTrait;
use Serafim\Vulkan\Struct\OutputStructureInterface;
use Serafim\Vulkan\Struct\OutputStructureTrait;
use Serafim\Vulkan\VulkanBase;

/**
 * Bases of the structures of the Vulkan API.
 */
final class StructArchitecture
{
    #[TestRule]
    public function testContractsDependOnlyOnCommands(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(InputStructureInterface::class),
                Selector::classname(InputStructureTrait::class),
                Selector::classname(OutputStructureInterface::class),
                Selector::classname(OutputStructureTrait::class),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(InputStructureInterface::class),
                Selector::classname(OutputStructureInterface::class),
                Selector::inNamespace('Serafim\Vulkan\Internal\Memory'),
                Selector::classname(InvalidStructureException::class),
                Selector::inNamespace('Serafim\Vulkan\Exception\InvalidStructure'),
                Selector::classname(Handle::class),
                Selector::classname(VulkanBase::class),
                Selector::classname(EnumCase::class),
            );
    }

    #[TestRule]
    public function testContractsAreInterfaces(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(InputStructureInterface::class),
                Selector::classname(OutputStructureInterface::class),
            )
            ->shouldBeInterface();
    }

    #[TestRule]
    public function testOutputStructuresAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::implements(OutputStructureInterface::class))
            ->excluding(Selector::classname(OutputStructureInterface::class))
            ->shouldBeReadonly();
    }

    #[TestRule]
    public function testInputStructuresAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::implements(InputStructureInterface::class))
            ->excluding(Selector::classname(InputStructureInterface::class))
            ->shouldBeFinal();
    }
}
