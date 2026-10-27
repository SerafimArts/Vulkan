
typedef void* GgpStreamDescriptor;
typedef void* GgpFrameToken;

typedef VkFlags VkStreamDescriptorSurfaceCreateFlagsGGP;

typedef struct VkStreamDescriptorSurfaceCreateInfoGGP {
    VkStructureType                            sType;
    const void*                                pNext;
    VkStreamDescriptorSurfaceCreateFlagsGGP    flags;
    GgpStreamDescriptor                        streamDescriptor;
} VkStreamDescriptorSurfaceCreateInfoGGP;


typedef VkResult ( *PFN_vkCreateStreamDescriptorSurfaceGGP)(VkInstance instance, const VkStreamDescriptorSurfaceCreateInfoGGP* pCreateInfo, const VkAllocationCallbacks* pAllocator, VkSurfaceKHR* pSurface);


typedef struct VkPresentFrameTokenGGP {
    VkStructureType    sType;
    const void*        pNext;
    GgpFrameToken      frameToken;
} VkPresentFrameTokenGGP;


 PFN_vkVoidFunction  vkGetInstanceProcAddr(
    VkInstance                                  instance,
    const char*                                 pName);
