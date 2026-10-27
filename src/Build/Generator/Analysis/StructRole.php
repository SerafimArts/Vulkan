<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * Who owns the structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum StructRole
{
    /**
     * The user: a mutable object written to native memory for a command.
     */
    case INPUT;

    /**
     * Vulkan: an immutable snapshot read from native memory.
     */
    case OUTPUT;

    /**
     * Both: the user creates it and Vulkan fills it. A mutable object that
     * is written to native memory and read back.
     */
    case DUAL;
}
