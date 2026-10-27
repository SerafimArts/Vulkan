<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\StructDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StructParser
{
    public function __construct(
        private Elements $elements,
        private FieldParser $fields,
    ) {}

    /**
     * @return array<non-empty-string, StructDefinition>
     */
    public function parse(): array
    {
        $structs = [];

        foreach ($this->elements->select("//types/type[@category='struct']") as $element) {
            $name = $element->getAttribute('name');

            if ($name === '' || $element->hasAttribute('alias') || !$this->elements->isSupported($element)) {
                continue;
            }

            $members = [];

            foreach ($this->elements->select('member', $element) as $member) {
                if ($this->elements->isSupported($member)) {
                    $members[] = $this->fields->parse($member);
                }
            }

            $structs[$name] = new StructDefinition(
                name: $name,
                members: $members,
                extends: $this->parseExtends($element->getAttribute('structextends')),
            );
        }

        return $structs;
    }

    /**
     * @return list<non-empty-string>
     */
    private function parseExtends(string $names): array
    {
        $extends = [];

        foreach (\explode(',', $names) as $name) {
            $name = $this->elements->createNullable(\trim($name));

            if ($name !== null) {
                $extends[] = $name;
            }
        }

        return $extends;
    }
}
