<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * What a C type is for the generated PHP code.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum TypeKind
{
    /**
     * An integer, including the flags and the typedefs of integers
     */
    case INTEGER;

    /**
     * A floating point number
     */
    case FLOAT;

    /**
     * `VkBool32`
     */
    case BOOLEAN;

    /**
     * `char`
     */
    case CHARACTER;

    /**
     * An enum of the specification
     */
    case ENUMERATION;

    /**
     * A handle of the specification
     */
    case HANDLE;

    /**
     * A structure of the specification
     */
    case STRUCTURE;

    /**
     * A type that has no PHP representation
     */
    case UNSUPPORTED;
}
