<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\Codecs;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\PropertyContext;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

/**
 * Reading of the members of a native struct to the properties.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Reader
{
    public function __construct(
        private TypeResolver $types,
        private Codecs $codecs,
    ) {}

    /**
     * Returns the expression that reads the member of `$data`.
     */
    public function createExpression(PhpNamespace $namespace, PropertyPlan $property, bool $isContextual = false): string
    {
        if ($property->field->bitWord !== null) {
            return $this->createBitFieldExpression($property->field);
        }

        return $this->codecs->get($property->kind)->createReadExpression(
            new PropertyContext($namespace, $property, isContextual: $isContextual),
        );
    }

    /**
     * Returns the type of a member that is not a property: the length of an
     * array.
     */
    public function createFieldType(FieldDefinition $member): string
    {
        return $this->types->getIntegerRange($member->type);
    }

    /**
     * Returns the type of the member of the native struct.
     */
    public function createType(PhpNamespace $namespace, PropertyPlan $property): string
    {
        return $this->codecs->get($property->kind)->createNativeType(new PropertyContext($namespace, $property));
    }

    /**
     * Returns the expression that extracts a bit-field from the word of the
     * native structure.
     */
    private function createBitFieldExpression(FieldDefinition $field): string
    {
        $mask = \sprintf('0x%X', 2 ** ($field->bits ?? 1) - 1);

        if ($field->bitOffset === 0) {
            return '$data->' . $field->bitWord . ' & ' . $mask;
        }

        return '$data->' . $field->bitWord . ' >> ' . $field->bitOffset . ' & ' . $mask;
    }
}
