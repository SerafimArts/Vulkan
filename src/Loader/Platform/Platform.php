<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Platform;

/**
 * Window system platform of the Vulkan API.
 */
enum Platform
{
    /** Android */
    case ANDROID;

    /** Google Fuchsia */
    case FUCHSIA;

    /** iOS (MoltenVK) */
    case IOS;

    /** macOS (MoltenVK) */
    case MACOS;

    /** Metal layer of Apple platforms */
    case METAL;

    /** Nintendo Switch (NN) */
    case VI;

    /** Wayland */
    case WAYLAND;

    /** Microsoft Windows */
    case WIN32;

    /** X Window System through XCB */
    case XCB;

    /** X Window System through Xlib */
    case XLIB;

    /** DirectFB */
    case DIRECTFB;

    /** Direct display access through Xlib RandR */
    case XLIB_XRANDR;

    /** Google Games Platform */
    case GGP;

    /** QNX Screen */
    case SCREEN;

    /** OpenHarmony */
    case OHOS;

    /** Samsung Unified Buffer Manager */
    case UBM;
}
