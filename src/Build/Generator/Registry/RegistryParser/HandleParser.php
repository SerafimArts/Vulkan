<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\HandleDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleParser
{
    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * @return array<non-empty-string, HandleDefinition>
     */
    public function parse(): array
    {
        $handles = [];

        foreach ($this->elements->select("//types/type[@category='handle']") as $element) {
            $name = $this->elements->readChildText($element, 'name');

            if ($name === '' || $element->hasAttribute('alias')) {
                continue;
            }

            $parent = \explode(',', $element->getAttribute('parent'))[0];

            $handles[$name] = new HandleDefinition($name, $this->elements->createNullable($parent));
        }

        return $handles;
    }
}
