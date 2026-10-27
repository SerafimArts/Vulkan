<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\EnumDefinition;

/**
 * The enums with the cases that the extensions add to them.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumParser
{
    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * @return array<non-empty-string, EnumDefinition>
     */
    public function parse(): array
    {
        /** @var array<non-empty-string, array<non-empty-string, int>> $cases */
        $cases = [];
        /** @var array<non-empty-string, array<non-empty-string, non-empty-string>> $aliases */
        $aliases = [];
        /** @var array<non-empty-string, array<non-empty-string, non-empty-string>> $comments */
        $comments = [];

        /** @var array<non-empty-string, true> $flags */
        $flags = [];

        foreach ($this->elements->select("//enums[@type='enum' or @type='bitmask']") as $enums) {
            $name = $enums->getAttribute('name');

            if ($name === '') {
                continue;
            }

            if ($enums->getAttribute('type') === 'bitmask') {
                $flags[$name] = true;
            }

            $cases[$name] = [];
            $aliases[$name] = [];
            $comments[$name] = [];

            foreach ($this->elements->select('enum', $enums) as $case) {
                $this->addCase($case, $cases[$name], $aliases[$name], $comments[$name], null);
            }
        }

        $extensions = '//feature//require/enum[@extends] | '
            . "//extensions/extension[not(@supported='disabled')]//require/enum[@extends]";

        foreach ($this->elements->select($extensions) as $case) {
            $extends = $case->getAttribute('extends');

            if (isset($cases[$extends])) {
                $this->addCase(
                    $case,
                    $cases[$extends],
                    $aliases[$extends],
                    $comments[$extends],
                    $this->findExtensionNumber($case),
                );
            }
        }

        $deprecated = $this->parseDeprecated();
        $enums = [];

        foreach ($cases as $name => $values) {
            $enums[$name] = new EnumDefinition(
                $name,
                $values,
                $aliases[$name],
                $comments[$name],
                \array_intersect_key($deprecated, $values + $aliases[$name]),
                isset($flags[$name]),
            );
        }

        return $enums;
    }

    /**
     * @return array<non-empty-string, true>
     */
    private function parseDeprecated(): array
    {
        $deprecated = [];

        foreach ($this->elements->select('//enum[@deprecated]') as $case) {
            $name = $this->elements->createNullable($case->getAttribute('name'));
            $isDeprecated = \in_array($case->getAttribute('deprecated'), ['true', 'aliased'], true);

            if ($name !== null && $isDeprecated) {
                $deprecated[$name] = true;
            }
        }

        return $deprecated;
    }

    /**
     * @param array<non-empty-string, int> $cases
     * @param array<non-empty-string, non-empty-string> $aliases
     * @param array<non-empty-string, non-empty-string> $comments
     */
    private function addCase(
        \DOMElement $element,
        array &$cases,
        array &$aliases,
        array &$comments,
        ?int $extensionNumber,
    ): void {
        $name = $element->getAttribute('name');

        if ($name === '' || !$this->elements->isSupported($element)) {
            return;
        }

        if ($element->hasAttribute('alias')) {
            $alias = $element->getAttribute('alias');

            if ($alias !== '') {
                $aliases[$name] = $alias;
            }

            return;
        }

        $value = $this->findCaseValue($element, $extensionNumber);

        if ($value !== null) {
            $cases[$name] = $value;

            $comment = $this->elements->createNullable(\trim($element->getAttribute('comment')));

            if ($comment !== null) {
                $comments[$name] = $comment;
            }
        }
    }

    private function findCaseValue(\DOMElement $element, ?int $extensionNumber): ?int
    {
        if ($element->hasAttribute('value')) {
            return \intval($element->getAttribute('value'), 0);
        }

        if ($element->hasAttribute('bitpos')) {
            return 1 << (int) $element->getAttribute('bitpos');
        }

        if ($element->hasAttribute('offset')) {
            $number = $extensionNumber;

            if ($element->hasAttribute('extnumber')) {
                $number = (int) $element->getAttribute('extnumber');
            }

            if ($number === null) {
                return null;
            }

            $value = 1000000000 + ($number - 1) * 1000 + (int) $element->getAttribute('offset');

            if ($element->getAttribute('dir') === '-') {
                return -$value;
            }

            return $value;
        }

        return null;
    }

    private function findExtensionNumber(\DOMElement $element): ?int
    {
        $parent = $element->parentNode;

        while ($parent !== null) {
            if ($parent instanceof \DOMElement && $parent->hasAttribute('number')) {
                return (int) $parent->getAttribute('number');
            }

            $parent = $parent->parentNode;
        }

        return null;
    }
}
