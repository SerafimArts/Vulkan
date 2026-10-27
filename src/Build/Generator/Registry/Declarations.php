<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * The Vulkan types the compiled headers declare. The registry describes more:
 * the beta extensions and Vulkan SC are not in the headers.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class Declarations
{
    private function __construct() {}

    /**
     * Reads the names of the types from the headers of a directory.
     *
     * @param non-empty-string $directory
     *
     * @return array<string, true> empty if there are no headers
     */
    public static function read(string $directory): array
    {
        /** @var array<string, true> $names */
        $names = [];
        $headers = \glob($directory . '/*.h');

        if ($headers === false) {
            return $names;
        }

        foreach ($headers as $header) {
            \preg_match_all('/\b(?:PFN_vk|Vk)\w+/', (string) \file_get_contents($header), $matches);

            foreach ($matches[0] as $name) {
                $names[$name] = true;
            }
        }

        return $names;
    }
}
