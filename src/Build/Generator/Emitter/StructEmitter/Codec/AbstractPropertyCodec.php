<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * The codec of a property that is neither written nor read in a special way.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
abstract readonly class AbstractPropertyCodec implements PropertyCodecInterface
{
    public function createWriteLines(PropertyContext $context): array
    {
        throw new \LogicException(\sprintf('The property of the kind %s is not written', $context->property->kind->name));
    }

    public function createReadExpression(PropertyContext $context): string
    {
        return $context->createMemberExpression();
    }

    public function createNativeType(PropertyContext $context): string
    {
        return 'CData';
    }

    /**
     * Writes the line when the property is given: a property that cannot be
     * {@see null} is written always.
     *
     * @return list<string>
     */
    protected function createNullableLines(PropertyContext $context, string $line): array
    {
        if ($context->property->isNullable) {
            return ['if (' . $context->createValueExpression() . ' !== null) {', '    ' . $line, '}'];
        }

        return [$line];
    }

    public function createPhpType(PropertyContext $context): string
    {
        $property = $context->property;

        if ($property->builtinType !== null) {
            return $property->builtinType;
        }

        $space = $this->getNamespace();

        Imports::import($context->namespace, $space, $property->field->type);

        return Locations::createClassName($space, $property->field->type);
    }

    public function createDocType(PropertyContext $context): ?string
    {
        return null;
    }

    public function importElementClass(PropertyContext $context): void {}

    /**
     * Returns the namespace of the class of the property when it is a class.
     */
    protected function getNamespace(): string
    {
        return Locations::STRUCT_NAMESPACE;
    }

    /**
     * Returns the type of the list of the elements of the class of the property.
     */
    protected function createListType(PropertyContext $context): string
    {
        return 'list<' . $context->property->field->type . '>';
    }

    /**
     * Imports the class of the elements of the property from the namespace.
     */
    protected function importFrom(PropertyContext $context, string $space): void
    {
        Imports::import($context->namespace, $space, $context->property->field->type);
    }
}
