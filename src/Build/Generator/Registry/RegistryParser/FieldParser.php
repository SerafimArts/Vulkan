<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

/**
 * A member of a structure or a parameter of a command.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FieldParser
{
    public function __construct(
        private Elements $elements,
    ) {}

    public function parse(\DOMElement $element): FieldDefinition
    {
        $prefix = '';
        $suffix = '';
        $trailing = '';
        $type = '';
        $name = '';
        $comment = '';

        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                if ($node->tagName === 'comment') {
                    $comment = \trim((string) \preg_replace('/\s+/', ' ', $node->textContent));
                } elseif ($node->tagName === 'type') {
                    $type = $node->textContent;
                } elseif ($node->tagName === 'name') {
                    $name = $node->textContent;
                } elseif ($node->tagName === 'enum') {
                    $trailing .= $node->textContent;
                }

                continue;
            }

            if ($type === '') {
                $prefix .= $node->textContent;
            } elseif ($name === '') {
                $suffix .= $node->textContent;
            } else {
                $trailing .= $node->textContent;
            }
        }

        [$arraySize, $unsupported] = $this->parseArraySize(\trim($trailing));
        $declaration = $element->getAttribute('len');

        if (\str_starts_with($declaration, 'latexmath')) {
            $declaration = $element->getAttribute('altlen');
        }

        [$length, $isNullTerminated] = $this->parseLength($declaration);
        $divisor = 1;

        if (\preg_match('/^(\w+) \/ (\d+)$/', $length ?? '', $matches) === 1) {
            $length = $matches[1];
            $divisor = \max(1, (int) $matches[2]);
        }

        return new FieldDefinition(
            name: $this->elements->createNullable($name) ?? 'unnamed',
            type: $this->elements->createNullable($type) ?? 'void',
            pointerDepth: \substr_count($suffix, '*'),
            isConst: \str_contains($prefix, 'const'),
            arraySize: $arraySize,
            length: $length,
            isNullTerminated: $isNullTerminated,
            isOptional: \str_starts_with($element->getAttribute('optional'), 'true'),
            value: $this->elements->createNullable($element->getAttribute('values')),
            unsupported: $unsupported,
            comment: $this->elements->createNullable($comment),
            limitType: $this->elements->createNullable($element->getAttribute('limittype')),
            lengthDivisor: $divisor,
        );
    }

    /**
     * @return array{string|null, string|null} the size and the reason why
     *         the declaration is not supported
     */
    private function parseArraySize(string $declaration): array
    {
        if ($declaration === '') {
            return [null, null];
        }

        if (\preg_match('/^\[([A-Za-z_0-9]+)\]$/', $declaration, $matches) === 1) {
            return [$matches[1], null];
        }

        return [null, 'array dimensions or bit-field [' . $declaration . ']'];
    }

    /**
     * @return array{string|null, bool} the name of the field that holds the
     *         length and whether the string is null-terminated
     */
    private function parseLength(string $declaration): array
    {
        if ($declaration === '') {
            return [null, false];
        }

        $parts = \explode(',', $declaration);

        return [
            $this->elements->createNullable(\str_replace('null-terminated', '', $parts[0])),
            \in_array('null-terminated', $parts, true),
        ];
    }
}
