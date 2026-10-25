#ifndef __DIRECTFB_H__
    #define __DIRECTFB_H__

    /*
     * Forward declaration macro for interfaces.
     */
    #define D_DECLARE_INTERFACE( IFACE )              \
         typedef struct _## IFACE IFACE;

    /*
     * Main interface of DirectFB, created by DirectFBCreate().
     */
    D_DECLARE_INTERFACE( IDirectFB )

    /*
     * Interface to a surface object, being a graphics context for rendering and state control,
     * buffer operations, palette access and sub area translate'n'clip logic.
     */
    D_DECLARE_INTERFACE( IDirectFBSurface )
#endif
