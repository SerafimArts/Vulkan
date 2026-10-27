<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Command\BuildCommand;

/**
 * Splits the headers of the platforms into the declarations that all of them
 * share and the rest of every platform. The shared part followed by the rest
 * of a platform is the headers of the platform.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HeadersSplitter
{
    private const int CHUNK_SIZE = 65536;

    /**
     * @param non-empty-array<non-empty-string, string> $headers the headers
     *        by the names of the platforms
     *
     * @return array{string, array<non-empty-string, string>} the shared part
     *         and the rest by the names of the platforms
     */
    public function split(array $headers): array
    {
        $shared = \reset($headers);

        foreach ($headers as $text) {
            $shared = \substr($shared, 0, $this->findCommonLength($shared, $text));
        }

        // The shared part ends with a whole line.
        $end = \strrpos($shared, "\n");
        $shared = \substr($shared, 0, (int) $end + 1);

        $tails = [];

        foreach ($headers as $platform => $text) {
            $tails[$platform] = \substr($text, \strlen($shared));
        }

        return [$shared, $tails];
    }

    /**
     * Returns the length of the common beginning of the texts.
     */
    private function findCommonLength(string $a, string $b): int
    {
        $length = \min(\strlen($a), \strlen($b));
        $offset = 0;

        while ($offset < $length) {
            $size = \min(self::CHUNK_SIZE, $length - $offset);

            if (\substr($a, $offset, $size) !== \substr($b, $offset, $size)) {
                break;
            }

            $offset += $size;
        }

        while ($offset < $length && $a[$offset] === $b[$offset]) {
            ++$offset;
        }

        return $offset;
    }
}
