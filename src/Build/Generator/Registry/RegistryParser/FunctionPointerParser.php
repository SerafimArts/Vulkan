<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\FunctionPointer;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FunctionPointerParser
{
    public function __construct(
        private Elements $elements,
        private FieldParser $fields,
    ) {}

    /**
     * @return array<non-empty-string, FunctionPointer>
     */
    public function parse(): array
    {
        $pointers = [];

        foreach ($this->elements->select("//types/type[@category='funcpointer']") as $element) {
            $proto = $this->elements->select('proto', $element)[0] ?? null;

            if ($proto === null) {
                continue;
            }

            $name = $this->elements->readChildText($proto, 'name');
            $returnType = $this->elements->readChildText($proto, 'type');

            if ($name === '' || $returnType === '') {
                continue;
            }

            $parameters = [];

            foreach ($this->elements->select('param', $element) as $parameter) {
                $parameters[] = $this->fields->parse($parameter);
            }

            $pointers[$name] = new FunctionPointer($name, $returnType, \substr_count($proto->textContent, '*'), $parameters);
        }

        return $pointers;
    }
}
