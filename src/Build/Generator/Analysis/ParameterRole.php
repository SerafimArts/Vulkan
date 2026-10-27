<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * What a parameter of a command is for the generated method.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum ParameterRole
{
    /**
     * A handle passed by the user.
     */
    case HANDLE;

    /**
     * A number or an enum passed by the user.
     */
    case SCALAR;

    /**
     * `const T*` of a structure of the user.
     */
    case INPUT_STRUCTURE;

    /**
     * `const VkAllocationCallbacks*`, always {@see null}.
     */
    case ALLOCATOR;

    /**
     * `T*` of a handle that is created by the command.
     */
    case OUTPUT_HANDLE;

    /**
     * `T*` of a structure that is filled by the command.
     */
    case OUTPUT_STRUCTURE;

    /**
     * `T*` of an array that is filled by the command.
     */
    case OUTPUT_ARRAY;

    /**
     * `uint32_t*` with the length of the output array.
     */
    case OUTPUT_COUNT;

    /**
     * `const T*` with the length in another parameter: structures, handles,
     * numbers or enums of the user.
     */
    case INPUT_ARRAY;

    /**
     * The length of an input array, it is the number of its elements.
     */
    case INPUT_COUNT;

    /**
     * `const char*` of a string of the user.
     */
    case INPUT_STRING;

    /**
     * `T*` of a number or an enum that is written by the command.
     */
    case OUTPUT_SCALAR;

    /**
     * `void**` that is filled with a pointer to native memory.
     */
    case OUTPUT_POINTER;

    /**
     * `const void*` with the size in bytes in another parameter: the bytes of
     * a string of the user.
     */
    case INPUT_BINARY;

    /**
     * `void*` with the size in bytes that is filled by the command.
     */
    case OUTPUT_BINARY;
}
