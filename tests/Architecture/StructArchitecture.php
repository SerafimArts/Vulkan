<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Struct\InputStructure;
use Serafim\Vulkan\Struct\OutputStructure;

/**
 * Bases of the structures of the Vulkan API.
 */
final class StructArchitecture
{
    #[TestRule]
    public function testBasesDependOnlyOnCommands(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(InputStructure::class),
                Selector::classname(OutputStructure::class),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(InputStructure::class),
                Selector::classname(OutputStructure::class),
                Selector::inNamespace('Serafim\Vulkan\Internal\Memory'),
                Selector::classname(InvalidStructureException::class),
            );
    }

    #[TestRule]
    public function testBasesAreAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::classname(InputStructure::class),
                Selector::classname(OutputStructure::class),
            )
            ->shouldBeAbstract();
    }

    #[TestRule]
    public function testOutputBaseIsReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(OutputStructure::class))
            ->shouldBeReadonly();
    }
}
