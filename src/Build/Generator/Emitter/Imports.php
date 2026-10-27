<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use Nette\PhpGenerator\PhpNamespace;

/**
 * Imports of the classes the generated code refers to.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class Imports
{
    /**
     * Imports the class and returns its short name.
     */
    public static function import(PhpNamespace $namespace, string $space, string $name): string
    {
        $namespace->addUse(Locations::createClassName($space, $name));

        return $name;
    }

    /**
     * Imports the class and returns its full name.
     */
    public static function importFullName(PhpNamespace $namespace, string $space, string $name): string
    {
        self::import($namespace, $space, $name);

        return Locations::createClassName($space, $name);
    }

    private function __construct() {}
}
