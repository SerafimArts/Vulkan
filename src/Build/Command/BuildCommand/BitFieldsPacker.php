<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Command\BuildCommand;

use Serafim\Vulkan\Build\Generator\Registry\BitFields;

/**
 * Replaces the runs of bit-fields of the structures by the words that hold
 * them: the layout of the bit-fields of `ext-ffi` does not follow the layout
 * of C.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BitFieldsPacker
{
    private const string BIT_FIELD_PATTERN = '/^(\s*)\w+\s+\w+\s*:\s*(\d{1,2})\s*;\s*$/';

    public function pack(string $headers): string
    {
        $lines = \explode("\n", $headers);
        $result = [];
        $run = [];
        $words = 0;

        foreach ([...$lines, null] as $line) {
            if ($line !== null && \preg_match(self::BIT_FIELD_PATTERN, $line, $matches) === 1) {
                $run[] = [$matches[1], (int) $matches[2]];

                continue;
            }

            if ($run !== []) {
                $words = $this->flush($run, $words, $result);
                $run = [];
            }

            if ($line === null) {
                continue;
            }

            if (\str_contains($line, '{') || \str_contains($line, '}')) {
                $words = 0;
            }

            $result[] = $line;
        }

        return \implode("\n", $result);
    }

    /**
     * Writes the words of a run of bit-fields and returns the number of the
     * words of the structure.
     *
     * @param non-empty-list<array{string, int}> $run the indentation and the
     *        width of every bit-field
     * @param list<string> $result
     *
     * @param-out list<string> $result
     */
    private function flush(array $run, int $words, array &$result): int
    {
        /** @var list<int<1, 64>> $widths */
        $widths = \array_map(static fn(array $field): int => $field[1], $run);
        $placements = BitFields::place($widths);
        $count = 0;

        foreach ($placements as $index => [$word]) {
            if ($word < $count) {
                continue;
            }

            $count = $word + 1;
            $result[] = $run[$index][0] . 'uint32_t ' . BitFields::createWordName($words + $word) . ';';
        }

        return $words + $count;
    }
}
