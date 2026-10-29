<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform;

/**
 * Window system platform of the Vulkan API.
 */
enum Platform
{
    /** macOS (MoltenVK) */
    case MACOS;

    /** Metal layer of Apple platforms */
    case METAL;

    /** Wayland */
    case WAYLAND;

    /** Microsoft Windows */
    case WIN32;

    /** X Window System through XCB */
    case XCB;

    /** X Window System through Xlib */
    case XLIB;

    /** Direct display access through Xlib RandR */
    case XLIB_XRANDR;
}
