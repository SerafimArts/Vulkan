<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;

/**
 * What a codec needs to know of the property it works with.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class PropertyContext
{
    public function __construct(
        public PhpNamespace $namespace,
        public PropertyPlan $property,
        /**
         * The name of the structure
         */
        public string $structure = '',
        /**
         * Whether an array that is written before has the same length member,
         * or the length is a property
         */
        public bool $isLengthShared = false,
        /**
         * Whether the property is the length of arrays
         */
        public bool $isLength = false,
        /**
         * Whether the structure holds handles and is read with the context
         */
        public bool $isContextual = false,
        /**
         * Whether the structure is read from native memory: a string that
         * Vulkan has written may be empty
         */
        public bool $isReturned = false,
    ) {}

    /**
     * Returns the expression of the property of the structure.
     */
    public function createValueExpression(): string
    {
        return '$this->' . $this->property->name;
    }

    /**
     * Returns the expression of the member of the native struct.
     */
    public function createMemberExpression(): string
    {
        return '$data->' . $this->property->field->name;
    }

    /**
     * Returns the expression of the member with the length of the array.
     */
    public function createLengthExpression(): string
    {
        $field = $this->property->field;

        if ($field->lengthStruct !== null) {
            return '$counts->' . ($field->length ?? '');
        }

        return '$data->' . ($field->length ?? '');
    }

    /**
     * Returns how many elements of the array are set: the structure counts
     * them in another member.
     */
    public function createListLengthExpression(): string
    {
        $count = $this->property->field->length;

        if ($count === null) {
            return (string) $this->property->arrayLength;
        }

        return '\min($data->' . $count . ', ' . $this->property->arrayLength . ')';
    }
}
