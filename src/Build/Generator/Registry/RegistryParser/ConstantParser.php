<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\Constant;

/**
 * The `#define` constants: the limits of the API, the versions and the names
 * and the versions of the extensions.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ConstantParser
{
    private const string EXTENSIONS = "//extensions/extension[contains(concat(',', @supported, ','), ',vulkan,')]";

    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * @return list<Constant>
     */
    public function parse(): array
    {
        $constants = [...$this->parseVersions(), ...$this->parseLimits(), ...$this->parseExtensions()];
        $unique = [];

        foreach ($constants as $constant) {
            $unique[$constant->name] ??= $constant;
        }

        return \array_values($unique);
    }

    /**
     * @return list<Constant>
     */
    private function parseVersions(): array
    {
        $constants = [];
        $latest = null;

        foreach ($this->elements->select("//feature[starts-with(@name, 'VK_VERSION_')]") as $feature) {
            \preg_match('/^(\d+)\.(\d+)$/', $feature->getAttribute('number'), $matches);

            if (!isset($matches[2])) {
                continue;
            }

            [, $major, $minor] = $matches;

            $constants[] = new Constant(
                'VK_API_VERSION_' . $major . '_' . $minor,
                '0 << 29 | ' . $major . ' << 22 | ' . $minor . ' << 12',
                '#define VK_API_VERSION_' . $major . '_' . $minor . ' VK_MAKE_API_VERSION(0, ' . $major . ', '
                    . $minor . ', 0)',
            );

            $latest = [$major, $minor];
        }

        $header = $this->elements->select("//types/type[@category='define'][name='VK_HEADER_VERSION']")[0] ?? null;

        if ($header === null || $latest === null) {
            return $constants;
        }

        if (\preg_match('/#define\s+VK_HEADER_VERSION\s+(\d+)/', $header->textContent, $matches) === 1) {
            $constants[] = new Constant('VK_HEADER_VERSION', $matches[1], '#define VK_HEADER_VERSION ' . $matches[1]);
            $constants[] = new Constant(
                'VK_HEADER_VERSION_COMPLETE',
                'VK_API_VERSION_' . $latest[0] . '_' . $latest[1] . ' | VK_HEADER_VERSION',
                '#define VK_HEADER_VERSION_COMPLETE VK_MAKE_API_VERSION(0, ' . $latest[0] . ', ' . $latest[1]
                    . ', VK_HEADER_VERSION)',
            );
        }

        return $constants;
    }

    /**
     * @return list<Constant>
     */
    private function parseLimits(): array
    {
        $constants = [];

        foreach ($this->elements->select("//enums[@name='API Constants']/enum") as $element) {
            $name = $element->getAttribute('name');
            $value = $element->getAttribute('value');
            $expression = $this->createExpression($value);

            if ($name === '' || $expression === null || !$this->elements->isSupported($element)) {
                continue;
            }

            $constants[] = new Constant(
                $name,
                $expression,
                '#define ' . $name . ' ' . $value,
                $this->elements->createNullable($element->getAttribute('comment')),
            );
        }

        return $constants;
    }

    /**
     * @return list<Constant>
     */
    private function parseExtensions(): array
    {
        $constants = [];

        foreach ($this->elements->select(self::EXTENSIONS . '//require/enum[not(@extends) and not(@alias)]') as $element) {
            $name = $element->getAttribute('name');
            $value = $element->getAttribute('value');

            $isExtensionConstant = \str_ends_with($name, '_SPEC_VERSION') || \str_ends_with($name, '_EXTENSION_NAME');

            if (!$isExtensionConstant || $value === '') {
                continue;
            }

            $expression = $this->createExpression($value);

            if ($expression !== null) {
                $constants[] = new Constant($name, $expression, '#define ' . $name . ' ' . $value);
            }
        }

        return $constants;
    }

    /**
     * Returns the PHP expression of a value of C, {@see null} if the value is
     * not a number or a string.
     *
     * @return non-empty-string|null
     */
    private function createExpression(string $value): ?string
    {
        if (\preg_match('/^"[^"\\\\]*"$/', $value) === 1) {
            return "'" . \substr($value, 1, -1) . "'";
        }

        if (\preg_match('/^(0x[0-9a-fA-F]+|\d+)$/', $value) === 1) {
            return $value;
        }

        if (\preg_match('/^(\d+\.\d+)[fF]$/', $value, $matches) === 1) {
            return $matches[1];
        }

        if (\preg_match('/^\(~(\d+)U\)$/', $value, $matches) === 1) {
            return '2 ** 32 - ' . ((int) $matches[1] + 1);
        }

        if ($value === '(~0ULL)') {
            return '-1';
        }

        return null;
    }
}
