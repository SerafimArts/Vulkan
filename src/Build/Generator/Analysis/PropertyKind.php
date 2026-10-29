<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * How a member of a structure is represented in PHP.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum PropertyKind
{
    /**
     * An integer or flags
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
     * An enum of the specification
     */
    case ENUMERATION;

    /**
     * `const char*`
     */
    case STRING;

    /**
     * `const char* const*` with the length in another member
     */
    case STRING_LIST;

    /**
     * `char[N]`
     */
    case TEXT;

    /**
     * `const void*` or `const uint32_t*` of bytes with the size in another
     * member
     */
    case BINARY;

    /**
     * `PFN_*`, the function that Vulkan calls: a closure of the user
     */
    case CALLBACK;

    /**
     * A value of the system (`HWND`) or a pointer (`void*`, `xcb_connection_t*`)
     * that the user gives as it is
     */
    case NATIVE;

    /**
     * `const uint32_t*` of the words of a mask of samples, the number of the
     * words follows the number of the samples
     */
    case SAMPLE_MASK;

    /**
     * `uint8_t[N]`, the bytes of an identifier
     */
    case BYTES;

    /**
     * `const T*` of one handle
     */
    case HANDLE_POINTER;

    /**
     * `const T*` of one number or enum
     */
    case SCALAR_POINTER;

    /**
     * `T[N]` of numbers
     */
    case NUMBER_LIST;

    /**
     * `const T*` of a structure
     */
    case STRUCTURE_POINTER;

    /**
     * `T` of a structure
     */
    case STRUCTURE_VALUE;

    /**
     * A handle
     */
    case HANDLE;

    /**
     * `T[N]` of handles
     */
    case HANDLE_LIST;

    /**
     * `T[N]` of enums
     */
    case ENUMERATION_LIST;

    /**
     * `T[N]` of structures
     */
    case STRUCTURE_LIST;

    /**
     * `T*` of a returned structure that Vulkan fills: the user gives only the
     * place for it
     */
    case FEEDBACK_STRUCTURE;

    /**
     * `T*` of returned structures that Vulkan fills, the number of the
     * elements is in another member the user gives
     */
    case FEEDBACK_ARRAY;

    /**
     * `T*` of numbers, enums or structures that Vulkan fills in a returned
     * structure, the number of the elements is in another member Vulkan reads
     * and writes
     */
    case QUERIED_ARRAY;

    /**
     * `void*` of the bytes that Vulkan fills in a returned structure, the size
     * is in another member
     */
    case QUERIED_BYTES;

    /**
     * `const T*` of structures with the length in another member
     */
    case STRUCTURE_ARRAY;

    /**
     * `const T*` of handles with the length in another member
     */
    case HANDLE_ARRAY;

    /**
     * `const T*` of numbers with the length in another member
     */
    case NUMBER_ARRAY;

    /**
     * `const T*` of enums with the length in another member
     */
    case ENUMERATION_ARRAY;

    /**
     * `pNext`, the next structure of the chain
     */
    case NEXT;

    /**
     * The structures of the chain that a command has filled: `pNext` of a
     * returned structure
     */
    case CHAIN;
}
