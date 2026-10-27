
typedef VkFlags VkUbmSurfaceCreateFlagsSEC;

typedef struct VkUbmSurfaceCreateInfoSEC {
    VkStructureType               sType;
    const void*                   pNext;
    VkUbmSurfaceCreateFlagsSEC    flags;
    struct ubm_device*            device;
    struct ubm_surface*           surface;
} VkUbmSurfaceCreateInfoSEC;


typedef VkResult ( *PFN_vkCreateUbmSurfaceSEC)(VkInstance instance, const VkUbmSurfaceCreateInfoSEC* pCreateInfo, const VkAllocationCallbacks* pAllocator, VkSurfaceKHR* pSurface);
typedef VkBool32 ( *PFN_vkGetPhysicalDeviceUbmPresentationSupportSEC)(VkPhysicalDevice physicalDevice, uint32_t queueFamilyIndex, struct ubm_device* device);

 PFN_vkVoidFunction  vkGetInstanceProcAddr(
    VkInstance                                  instance,
    const char*                                 pName);
