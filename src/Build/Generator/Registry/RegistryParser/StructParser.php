<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\BitFields;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
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

        foreach ($this->elements->select("//types/type[@category='struct' or @category='union']") as $element) {
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
                members: $this->packBitFields($members),
                extends: $this->parseExtends($element->getAttribute('structextends')),
                isUnion: $element->getAttribute('category') === 'union',
                isReturnedOnly: $element->getAttribute('returnedonly') === 'true',
            );
        }

        return $structs;
    }

    /**
     * Assigns the words of the native structure to the runs of bit-fields.
     *
     * @param list<FieldDefinition> $members
     *
     * @return list<FieldDefinition>
     */
    private function packBitFields(array $members): array
    {
        $result = [];
        $run = [];
        $words = 0;

        foreach ([...$members, null] as $member) {
            if ($member !== null && $member->bits !== null) {
                $run[] = $member;

                continue;
            }

            $placements = BitFields::place(\array_map(static fn(FieldDefinition $field): int => $field->bits ?? 1, $run));
            $next = $words;

            foreach ($run as $index => $field) {
                [$word, $offset] = $placements[$index];
                $next = \max($next, $words + $word + 1);

                $result[] = new FieldDefinition(
                    name: $field->name,
                    type: $field->type,
                    pointerDepth: $field->pointerDepth,
                    isConst: $field->isConst,
                    arraySize: $field->arraySize,
                    length: $field->length,
                    isNullTerminated: $field->isNullTerminated,
                    isOptional: $field->isOptional,
                    value: $field->value,
                    unsupported: $field->unsupported,
                    comment: $field->comment,
                    limitType: $field->limitType,
                    lengthDivisor: $field->lengthDivisor,
                    bits: $field->bits,
                    isMultidimensional: $field->isMultidimensional,
                    bitWord: BitFields::createWordName($words + $word),
                    bitOffset: $offset,
                );
            }

            $words = $next;
            $run = [];

            if ($member !== null) {
                $result[] = $member;
            }
        }

        return $result;
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
