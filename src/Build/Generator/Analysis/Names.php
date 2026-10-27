<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * PHP names of the Vulkan declarations.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class Names
{
    /**
     * Returns the name of a property or a parameter: the Hungarian prefix
     * of a pointer (`p`, `pp`) is dropped, the rest keeps its spelling.
     */
    public static function createVariableName(string $name): string
    {
        if (\preg_match('/^pp?([A-Z].*)$/', $name, $matches) === 1) {
            return \lcfirst($matches[1]);
        }

        return $name;
    }

    private function __construct() {}
}
