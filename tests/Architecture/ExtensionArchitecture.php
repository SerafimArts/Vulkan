<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Extensions of the static analysis tools for the users of the library.
 */
final class ExtensionArchitecture
{
    private const string NAMESPACE = 'Serafim\Vulkan\Extension';

    #[TestRule]
    public function testExtensionsDependOnlyOnTheToolsTheyExtend(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace(self::NAMESPACE),
                Selector::inNamespace('PHPStan'),
                Selector::inNamespace('PhpParser'),
            );
    }

    #[TestRule]
    public function testClassesAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::NAMESPACE))
            ->shouldBeFinal();
    }
}
