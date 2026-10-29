<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * How a member of a structure is represented in PHP.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
enum PropertyKind
{
    /**
     * An integer or flags
     */
    case INTEGER;

    /**
     * A floating point number
     */
    case FLOAT;

    /**
     * `VkBool32`
     */
    case BOOLEAN;

    /**
     * An enum of the specification
     */
    case ENUMERATION;

    /**
     * `const char*`
     */
    case STRING;

    /**
     * `const char* const*` with the length in another member
     */
    case STRING_LIST;

    /**
     * `char[N]`
     */
    case TEXT;

    /**
     * `const void*` or `const uint32_t*` of bytes with the size in another
     * member
     */
    case BINARY;

    /**
     * `PFN_*`, the function that Vulkan calls: a closure of the user
     */
    case CALLBACK;

    /**
     * A value of the system (`HWND`) or a pointer (`void*`, `xcb_connection_t*`)
     * that the user gives as it is
     */
    case NATIVE;

    /**
     * `const uint32_t*` of the words of a mask of samples, the number of the
     * words follows the number of the samples
     */
    case SAMPLE_MASK;

    /**
     * `uint8_t[N]`, the bytes of an identifier
     */
    case BYTES;

    /**
     * `const T*` of one handle
     */
    case HANDLE_POINTER;

    /**
     * `const T*` of one number or enum
     */
    case SCALAR_POINTER;

    /**
     * `T[N]` of numbers
     */
    case NUMBER_LIST;

    /**
     * `const T*` of a structure
     */
    case STRUCTURE_POINTER;

    /**
     * `T` of a structure
     */
    case STRUCTURE_VALUE;

    /**
     * A handle
     */
    case HANDLE;

    /**
     * `T[N]` of handles
     */
    case HANDLE_LIST;

    /**
     * `T[N]` of enums
     */
    case ENUMERATION_LIST;

    /**
     * `T[N]` of structures
     */
    case STRUCTURE_LIST;

    /**
     * `T*` of a returned structure that Vulkan fills: the user gives only the
     * place for it
     */
    case FEEDBACK_STRUCTURE;

    /**
     * `T*` of returned structures that Vulkan fills, the number of the
     * elements is in another member the user gives
     */
    case FEEDBACK_ARRAY;

    /**
     * `T*` of numbers, enums or structures that Vulkan fills in a returned
     * structure, the number of the elements is in another member Vulkan reads
     * and writes
     */
    case QUERIED_ARRAY;

    /**
     * `void*` of the bytes that Vulkan fills in a returned structure, the size
     * is in another member
     */
    case QUERIED_BYTES;

    /**
     * `const T*` of structures with the length in another member
     */
    case STRUCTURE_ARRAY;

    /**
     * `const T*` of handles with the length in another member
     */
    case HANDLE_ARRAY;

    /**
     * `const T*` of numbers with the length in another member
     */
    case NUMBER_ARRAY;

    /**
     * `const T*` of enums with the length in another member
     */
    case ENUMERATION_ARRAY;

    /**
     * `const void* const*` with the length in another member: the pointers
     * to the values the user gives
     */
    case VALUE_POINTER_ARRAY;

    /**
     * `size_t*` that Vulkan reads and writes: the user gives the size, Vulkan
     * writes the size it has used
     */
    case INOUT_INTEGER;

    /**
     * `const VkBool32*` with the length in another member
     */
    case BOOLEAN_ARRAY;

    /**
     * `const T*` of numbers, enums or structures that the driver owns in a
     * returned structure, the length is in another member
     */
    case OWNED_ARRAY;

    /**
     * `VkResult*` that Vulkan fills: the user gives only the place for it
     */
    case FEEDBACK_ENUMERATION;

    /**
     * `pNext`, the next structure of the chain
     */
    case NEXT;

    /**
     * The structures of the chain that a command has filled: `pNext` of a
     * returned structure
     */
    case CHAIN;

    /**
     * The kinds a member of a structure of the user can have
     */
    private const array IN_INPUT = [
        self::INTEGER,
        self::FLOAT,
        self::BOOLEAN,
        self::ENUMERATION,
        self::STRING,
        self::STRING_LIST,
        self::BINARY,
        self::NATIVE,
        self::CALLBACK,
        self::SCALAR_POINTER,
        self::HANDLE_POINTER,
        self::SAMPLE_MASK,
        self::TEXT,
        self::BYTES,
        self::NUMBER_LIST,
        self::STRUCTURE_LIST,
        self::ENUMERATION_LIST,
        self::STRUCTURE_POINTER,
        self::FEEDBACK_STRUCTURE,
        self::FEEDBACK_ARRAY,
        self::STRUCTURE_VALUE,
        self::HANDLE,
        self::STRUCTURE_ARRAY,
        self::HANDLE_ARRAY,
        self::NUMBER_ARRAY,
        self::ENUMERATION_ARRAY,
        self::BOOLEAN_ARRAY,
        self::VALUE_POINTER_ARRAY,
        self::INOUT_INTEGER,
        self::FEEDBACK_ENUMERATION,
        self::NEXT,
    ];

    /**
     * The kinds a member of a structure that the user allocates and Vulkan
     * fills can have
     */
    private const array IN_DUAL = [
        self::INTEGER,
        self::FLOAT,
        self::BOOLEAN,
        self::ENUMERATION,
        self::TEXT,
        self::BYTES,
        self::NATIVE,
        self::NUMBER_LIST,
        self::STRUCTURE_VALUE,
        self::STRUCTURE_LIST,
        self::ENUMERATION_LIST,
        self::NEXT,
    ];

    /**
     * The pointers a union holds are not read: which one is valid depends on
     * another member.
     */
    private const array IN_UNION = [
        self::STRING,
        self::NATIVE,
        self::STRUCTURE_POINTER,
        self::CALLBACK,
    ];

    /**
     * The kinds a member of a returned structure can have
     */
    private const array IN_OUTPUT = [
        self::INTEGER,
        self::FLOAT,
        self::BOOLEAN,
        self::ENUMERATION,
        self::STRING,
        self::TEXT,
        self::BYTES,
        self::NATIVE,
        self::HANDLE,
        self::HANDLE_LIST,
        self::NUMBER_LIST,
        self::STRUCTURE_VALUE,
        self::STRUCTURE_LIST,
        self::ENUMERATION_LIST,
        self::QUERIED_ARRAY,
        self::QUERIED_BYTES,
        self::OWNED_ARRAY,
    ];

    /**
     * Whether a structure of the role can have a member of the kind.
     */
    public function isAllowedIn(StructRole $role): bool
    {
        $allowed = match ($role) {
            StructRole::INPUT => self::IN_INPUT,
            StructRole::OUTPUT => self::IN_OUTPUT,
            StructRole::DUAL => self::IN_DUAL,
        };

        return \in_array($this, $allowed, true);
    }

    /**
     * Whether the pointer is read from a union that is read: which pointer is
     * valid depends on another member, the others are not.
     */
    public function isPointer(): bool
    {
        return \in_array($this, self::IN_UNION, true);
    }

    /**
     * Whether Vulkan fills the array or the bytes after the library has
     * allocated them for the lengths the command has written.
     */
    public function isQueried(): bool
    {
        return $this === self::QUERIED_ARRAY || $this === self::QUERIED_BYTES;
    }

    /**
     * Whether the property is filled by Vulkan while a command uses the
     * structure the user has written.
     */
    public function isResult(): bool
    {
        return $this === self::FEEDBACK_STRUCTURE
            || $this === self::FEEDBACK_ARRAY
            || $this === self::FEEDBACK_ENUMERATION;
    }

    /**
     * Whether the array the user gives has its length in another member of
     * the structure.
     */
    public function isCounted(): bool
    {
        return \in_array($this, [
            self::STRING_LIST,
            self::BINARY,
            self::STRUCTURE_ARRAY,
            self::HANDLE_ARRAY,
            self::NUMBER_ARRAY,
            self::ENUMERATION_ARRAY,
            self::BOOLEAN_ARRAY,
            self::VALUE_POINTER_ARRAY,
        ], true);
    }

    /**
     * Whether the length member of the array stays a property of the
     * structure: it is not the number of the elements of the array.
     */
    public function keepsLength(): bool
    {
        return $this === self::SAMPLE_MASK || $this === self::NATIVE || $this === self::FEEDBACK_ARRAY;
    }

    /**
     * Whether the property refers to other structures that are generated
     * together with it.
     */
    public function isNested(): bool
    {
        return \in_array($this, [
            self::STRUCTURE_POINTER,
            self::STRUCTURE_VALUE,
            self::STRUCTURE_ARRAY,
            self::STRUCTURE_LIST,
        ], true);
    }
}
