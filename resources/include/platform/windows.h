typedef void* HANDLE;
typedef HANDLE HINSTANCE;
typedef HANDLE HWND;
typedef HANDLE HMONITOR;

typedef const unsigned short* LPCWSTR;

typedef unsigned long DWORD;
typedef void *LPVOID;
typedef int WINBOOL;
typedef struct _SECURITY_ATTRIBUTES {
    DWORD nLength;
    LPVOID lpSecurityDescriptor;
    WINBOOL bInheritHandle;
} SECURITY_ATTRIBUTES;
