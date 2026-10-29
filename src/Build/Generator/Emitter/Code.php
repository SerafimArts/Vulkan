<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * Lines of PHP code that are put together with their indentation.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Code
{
    private const string INDENT = '    ';

    private function __construct() {}

    /**
     * Shifts the lines to the right, the empty ones stay empty.
     *
     * @param list<string> $lines
     *
     * @return list<string>
     */
    public static function indent(array $lines, int $levels = 1): array
    {
        $indent = \str_repeat(self::INDENT, $levels);

        return \array_map(
            static fn(string $line): string => \rtrim($indent . $line),
            $lines,
        );
    }

    /**
     * Returns the lines of a block: the opening line, the indented body and
     * the closing line.
     *
     * @param list<string> $body
     *
     * @return list<string>
     */
    public static function block(string $open, array $body, string $close = '}'): array
    {
        return [$open, ...self::indent($body), $close];
    }

    /**
     * Returns the lines of a call with one argument on every line.
     *
     * @param list<string> $arguments the expressions, a multiline expression
     *        is a list of its lines
     *
     * @return list<string>
     */
    public static function call(string $callee, array $arguments, string $close = ');'): array
    {
        return self::block($callee . '(', $arguments, $close);
    }
}
