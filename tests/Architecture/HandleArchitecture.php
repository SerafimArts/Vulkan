<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\VulkanBase;

/**
 * Handles of the objects of Vulkan.
 */
final class HandleArchitecture
{
    #[TestRule]
    public function testBaseDependsOnlyOnCommands(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Handle::class))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname(Handle::class),
                Selector::classname(VulkanBase::class),
            );
    }

    #[TestRule]
    public function testBaseIsAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Handle::class))
            ->shouldBeAbstract();
    }

    #[TestRule]
    public function testBaseIsReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname(Handle::class))
            ->shouldBeReadonly();
    }
}
