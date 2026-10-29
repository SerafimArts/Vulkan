<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const VkBool32*` with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BooleanArrayCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        return '$memory->toNumberArray(' . \var_export($context->property->field->type, true)
            . ', \array_map(static fn(bool $item): int => (int) $item, ' . $context->createValueExpression() . '))';
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<bool>';
    }
}
