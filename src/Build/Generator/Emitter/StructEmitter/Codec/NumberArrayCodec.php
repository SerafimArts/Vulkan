<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const T*` of numbers with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NumberArrayCodec extends ArrayCodec
{
    public function __construct(
        private NumberTypes $numbers,
    ) {}

    protected function createAllocation(PropertyContext $context): string
    {
        return '$memory->toNumberArray(' . \var_export($context->property->field->type, true) . ', '
            . $context->createValueExpression() . ')';
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<' . $this->numbers->create($context->property) . '>';
    }
}
