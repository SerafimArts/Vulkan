<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * `const char* const*` with the length in another member.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class StringListCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        return '$memory->toCStringArray(' . $context->createValueExpression() . ')';
    }

    protected function createPointerType(PropertyContext $context): string
    {
        return 'char**';
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<string>';
    }
}
