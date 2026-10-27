<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

/**
 * Access to the elements of `vk.xml`.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Elements
{
    public function __construct(
        private \DOMXPath $xpath,
    ) {}

    /**
     * @return list<\DOMElement>
     */
    public function select(string $query, ?\DOMNode $context = null): array
    {
        $nodes = $this->xpath->query($query, $context);
        $elements = [];

        if ($nodes === false) {
            return $elements;
        }

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    public function readChildText(\DOMElement $element, string $tag): string
    {
        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMElement && $node->tagName === $tag) {
                return $node->textContent;
            }
        }

        return '';
    }

    /**
     * Whether the definition belongs to Vulkan, not to Vulkan SC.
     */
    public function isSupported(\DOMElement $element): bool
    {
        return $element->getAttribute('api') !== 'vulkansc';
    }

    /**
     * @return non-empty-string|null
     */
    public function createNullable(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }
}
