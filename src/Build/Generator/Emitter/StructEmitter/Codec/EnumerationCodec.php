<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Internal\EnumCase;

/**
 * An enum of the specification.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumerationCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        return [$context->createMemberExpression() . ' = ' . $context->createValueExpression() . '->value;'];
    }

    public function createReadExpression(PropertyContext $context): string
    {
        $context->namespace->addUse(EnumCase::class);

        return 'EnumCase::createFromValue('
            . Imports::import($context->namespace, Locations::ENUM_NAMESPACE, $context->property->field->type)
            . '::class, ' . $context->createMemberExpression() . ')';
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'value-of<'
            . Imports::import($context->namespace, Locations::ENUM_NAMESPACE, $context->property->field->type) . '>';
    }

    protected function getNamespace(): string
    {
        return Locations::ENUM_NAMESPACE;
    }
}
