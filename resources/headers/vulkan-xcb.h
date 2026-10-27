
    typedef uint32_t xcb_window_t;
    typedef uint32_t xcb_visualid_t;

    typedef struct xcb_connection_t xcb_connection_t;

typedef VkFlags VkXcbSurfaceCreateFlagsKHR;

typedef struct VkXcbSurfaceCreateInfoKHR {
    VkStructureType               sType;
    const void*                   pNext;
    VkXcbSurfaceCreateFlagsKHR    flags;
    xcb_connection_t*             connection;
    xcb_window_t                  window;
} VkXcbSurfaceCreateInfoKHR;


typedef VkResult ( *PFN_vkCreateXcbSurfaceKHR)(VkInstance instance, const VkXcbSurfaceCreateInfoKHR* pCreateInfo, const VkAllocationCallbacks* pAllocator, VkSurfaceKHR* pSurface);
typedef VkBool32 ( *PFN_vkGetPhysicalDeviceXcbPresentationSupportKHR)(VkPhysicalDevice physicalDevice, uint32_t queueFamilyIndex, xcb_connection_t* connection, xcb_visualid_t visual_id);

 PFN_vkVoidFunction  vkGetInstanceProcAddr(
    VkInstance                                  instance,
    const char*                                 pName);
