<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

/**
 * The scalar types and the constants of the specification.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class TypeParser
{
    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * Returns the C types of the flag types and of their bits by the names.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function parseBitmasks(): array
    {
        $bitmasks = [];

        foreach ($this->elements->select("//types/type[@category='bitmask']") as $element) {
            $name = $this->elements->readChildText($element, 'name');
            $type = $this->elements->readChildText($element, 'type');

            if ($name !== '' && $type !== '') {
                $bitmasks[$name] = $type;
            }
        }

        foreach ($this->elements->select("//enums[@type='bitmask']") as $element) {
            $name = $element->getAttribute('name');

            if ($name !== '') {
                $bitmasks[$name] = $element->getAttribute('bitwidth') === '64' ? 'VkFlags64' : 'VkFlags';
            }
        }

        return $bitmasks;
    }

    /**
     * Returns the names of the types of the bits by the names of the flag
     * types.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function parseFlagBits(): array
    {
        $bits = [];

        foreach ($this->elements->select("//types/type[@category='bitmask']") as $element) {
            $name = $this->elements->readChildText($element, 'name');
            $type = $element->getAttribute('requires') . $element->getAttribute('bitvalues');

            if ($name !== '' && $type !== '') {
                $bits[$name] = $type;
            }
        }

        return $bits;
    }

    /**
     * Returns the C types by the names of the typedefs.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function parseBaseTypes(): array
    {
        $types = [];

        foreach ($this->elements->select("//types/type[@category='basetype']") as $element) {
            $name = $this->elements->readChildText($element, 'name');
            $type = $this->elements->readChildText($element, 'type');

            if ($name !== '' && $type !== '') {
                $types[$name] = $type;
            }
        }

        return $types;
    }

    /**
     * @return array<non-empty-string, int>
     */
    public function parseConstants(): array
    {
        $constants = [];

        foreach ($this->elements->select("//enums[@name='API Constants']/enum") as $element) {
            $name = $element->getAttribute('name');
            $value = $element->getAttribute('value');

            if ($name !== '' && \preg_match('/^(0x[0-9a-fA-F]+|\d+)$/', $value) === 1) {
                $constants[$name] = \intval($value, 0);
            }
        }

        return $constants;
    }
}
