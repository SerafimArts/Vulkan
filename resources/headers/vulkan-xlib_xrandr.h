
    typedef unsigned long XID;
    typedef XID Window;
    typedef unsigned long VisualID;

    typedef struct _XDisplay Display;

    typedef XID RROutput;

typedef VkResult ( *PFN_vkAcquireXlibDisplayEXT)(VkPhysicalDevice physicalDevice, Display* dpy, VkDisplayKHR display);
typedef VkResult ( *PFN_vkGetRandROutputDisplayEXT)(VkPhysicalDevice physicalDevice, Display* dpy, RROutput rrOutput, VkDisplayKHR* pDisplay);

 PFN_vkVoidFunction  vkGetInstanceProcAddr(
    VkInstance                                  instance,
    const char*                                 pName);
