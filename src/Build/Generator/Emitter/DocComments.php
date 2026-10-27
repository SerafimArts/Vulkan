<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * Turns a comment of the registry into the lines of a docblock.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class DocComments
{
    private const int WIDTH = 64;

    /**
     * @return list<string>
     */
    public static function createLines(string $comment): array
    {
        $text = \str_replace(['—', '–', ';', '*/'], ['-', '-', ',', '* /'], $comment);
        $text = \ucfirst(\trim($text));

        if ($text === '') {
            return [];
        }

        return \explode("\n", \wordwrap($text, self::WIDTH));
    }
}
