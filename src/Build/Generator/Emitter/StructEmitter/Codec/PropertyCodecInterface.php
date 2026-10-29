<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * How a property of a generated structure is written to the member of a native
 * struct, read back from it and described in the docblock.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
interface PropertyCodecInterface
{
    /**
     * Returns the lines that write the property to `$data`.
     *
     * @return list<string>
     */
    public function createWriteLines(PropertyContext $context): array;

    /**
     * Returns the expression that reads the member of `$data`.
     */
    public function createReadExpression(PropertyContext $context): string;

    /**
     * Returns the type of the member of the native struct.
     */
    public function createNativeType(PropertyContext $context): string;

    /**
     * Returns the PHP type of the property.
     */
    public function createPhpType(PropertyContext $context): string;

    /**
     * Returns the type of the property for the docblock when it is stricter
     * than the PHP one, {@see null} otherwise.
     */
    public function createDocType(PropertyContext $context): ?string;

    /**
     * Imports the class of the elements of an array, the docblock refers to it.
     */
    public function importElementClass(PropertyContext $context): void;
}
