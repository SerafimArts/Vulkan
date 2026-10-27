<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * Layout of consecutive bit-fields of a structure. The bit-fields are stored
 * in the words of the native structure, because the layout of the bit-fields
 * of `ext-ffi` does not follow the layout of C.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class BitFields
{
    /**
     * Width of the word the bit-fields are packed to.
     */
    public const int WORD_BITS = 32;

    /**
     * Returns the name of the word of the native structure.
     *
     * @return non-empty-string
     */
    public static function createWordName(int $index): string
    {
        return 'bits' . $index;
    }

    /**
     * Returns the word and the offset of the lowest bit for every bit-field of
     * a run of consecutive bit-fields. A bit-field never crosses the border
     * of a word.
     *
     * @param list<int<1, 64>> $widths
     *
     * @return list<array{int<0, max>, int<0, max>}>
     */
    public static function place(array $widths): array
    {
        $placements = [];
        $word = 0;
        $used = 0;

        foreach ($widths as $width) {
            if ($used + $width > self::WORD_BITS) {
                ++$word;
                $used = 0;
            }

            $placements[] = [$word, $used];
            $used += $width;
        }

        return $placements;
    }

    private function __construct() {}
}
