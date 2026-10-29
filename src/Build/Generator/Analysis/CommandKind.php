<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * The scheme a command is built after.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum CommandKind
{
    /**
     * Returns nothing.
     */
    case EXECUTE;

    /**
     * Creates a handle.
     */
    case CREATE;

    /**
     * Creates several handles, the number of them is known beforehand.
     */
    case CREATE_ARRAY;

    /**
     * Creates several handles, the structure the command fills reports the
     * number of them and holds them.
     */
    case CREATE_HANDLES;

    /**
     * Fills a structure.
     */
    case QUERY;

    /**
     * Fills several outputs: they are returned by their names.
     */
    case QUERY_MULTIPLE;

    /**
     * Fills an array of structures or numbers, the number of them is known
     * beforehand.
     */
    case QUERY_ARRAY;

    /**
     * Fills the bytes of the size the user gives.
     */
    case QUERY_BINARY;

    /**
     * Writes a number to a variable.
     */
    case QUERY_SCALAR;

    /**
     * Writes a pointer to native memory to a variable.
     */
    case QUERY_POINTER;

    /**
     * Fills an array of unknown length.
     */
    case ENUMERATE;
}
