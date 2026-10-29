<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use FFI\CData;

/**
 * `const void* const*` with the length in another member: the user gives the
 * native values the pointers point to.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ValuePointerArrayCodec extends ArrayCodec
{
    protected function createAllocation(PropertyContext $context): string
    {
        return '$memory->toValuePointerArray(' . $context->createValueExpression() . ')';
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<CData>';
    }

    public function importElementClass(PropertyContext $context): void
    {
        $context->namespace->addUse(CData::class);
    }
}
