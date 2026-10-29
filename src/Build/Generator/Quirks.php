<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator;

/**
 * What the registry does not tell and the generator has to know: the members
 * the user fills in the structures Vulkan fills, the defaults that are not
 * zero and what the library does not cover. The declarations that need code
 * of their own are written by the processors.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Quirks
{
    /**
     * The structures that the user fills in part and Vulkan fills in the
     * rest: the member that the user gives by the name of the structure. The
     * command takes it as a list of values and returns the structures as they
     * are read.
     */
    private const array MIXED_INPUTS = [
        'VkDataGraphPipelinePropertyQueryResultARM' => 'property',
    ];

    /**
     * The returned structures whose arrays are allocated for the lengths of
     * another structure, the one the first call of the command fills: the
     * structure with the lengths and the member with the length by the member
     * with the array.
     */
    private const array COUNTED = [
        'VkDeviceFaultInfoEXT' => [
            'VkDeviceFaultCountsEXT',
            [
                'pAddressInfos' => 'addressInfoCount',
                'pVendorInfos' => 'vendorInfoCount',
                'pVendorBinaryData' => 'vendorBinarySize',
            ],
        ],
    ];

    /**
     * Defaults of the members for which zero is not a usable value.
     */
    private const array DEFAULTS = [
        'VkApplicationInfo::apiVersion' => 1 << 22,
    ];

    /**
     * The structures that gather the members of the structures a version of
     * Vulkan has promoted to the core (`VkPhysicalDeviceVulkan13Features`):
     * the number of the version without the dot and the kind of the structure.
     * The one of a newer version holds the members of the older ones.
     */
    private const string VERSIONED = '/^VkPhysicalDeviceVulkan(\d+)(Properties|Features)$/';

    /**
     * The platforms of `vk.xml` the library does not cover, the beta
     * extensions are marked with `provisional`.
     */
    private const array EXCLUDED_PLATFORMS = [
        'android',
        'directfb',
        'fuchsia',
        'ggp',
        'ios',
        'ohos',
        'provisional',
        'sci',
        'screen',
        'ubm',
        'vi',
    ];

    /**
     * The C type of the allocation callbacks, they are not supported and every
     * command passes `NULL`.
     */
    public const string ALLOCATOR_TYPE = 'VkAllocationCallbacks';

    /**
     * The commands that bind a handle to another one start with the prefix:
     * the second handle of the command keeps the next ones alive.
     */
    private const string BINDING_PREFIX = 'vkBind';

    /**
     * The commands that write the structures with the handle of the owner:
     * the type of the structures and the member of the owner. The owner keeps
     * the structure alive.
     */
    private const array WRITING_COMMANDS = [
        'vkUpdateDescriptorSets' => ['VkWriteDescriptorSet', 'dstSet'],
    ];

    /**
     * The extensions of video are not covered either.
     */
    private const string EXCLUDED_EXTENSIONS = '/^VK_[A-Z]+_video_/';

    /**
     * Returns the name of the member of the structure that the user gives.
     *
     * @return non-empty-string|null
     */
    public static function findMixedInput(string $struct): ?string
    {
        return self::MIXED_INPUTS[$struct] ?? null;
    }

    /**
     * Returns the name of the structure with the lengths of the arrays of the
     * structure and the members with the lengths by the members with the
     * arrays, {@see null} if the structure has the lengths itself.
     *
     * @return array{non-empty-string, array<non-empty-string, non-empty-string>}|null
     */
    public static function findCounts(string $struct): ?array
    {
        return self::COUNTED[$struct] ?? null;
    }

    /**
     * Returns the kind of the structure that gathers the members of a version
     * of Vulkan and the number of the version, {@see null} if the structure is
     * not one.
     *
     * @return array{non-empty-string, int<0, max>}|null
     */
    public static function findVersion(string $struct): ?array
    {
        if (\preg_match(self::VERSIONED, $struct, $matches) !== 1) {
            return null;
        }

        /** @var non-empty-string $kind */
        $kind = $matches[2];

        /** @var int<0, max> $number */
        $number = (int) $matches[1];

        return [$kind, $number];
    }

    /**
     * Returns the default value of the member, {@see null} if zero is usable.
     *
     * @param string $declaration the structure and the member, `VkStruct::member`
     */
    public static function findDefault(string $declaration): ?int
    {
        return self::DEFAULTS[$declaration] ?? null;
    }

    /**
     * Whether the command binds the handles to each other.
     */
    public static function isBinding(string $command): bool
    {
        return \str_starts_with($command, self::BINDING_PREFIX);
    }

    /**
     * Returns the type of the structures that the command writes and the
     * member of the structure with the handle that keeps the structure alive,
     * {@see null} if the command writes none.
     *
     * @return array{non-empty-string, non-empty-string}|null
     */
    public static function findWriting(string $command): ?array
    {
        return self::WRITING_COMMANDS[$command] ?? null;
    }

    /**
     * Whether the library does not cover the extension.
     */
    public static function isExcludedExtension(string $name, string $platform): bool
    {
        return \in_array($platform, self::EXCLUDED_PLATFORMS, true)
            || \preg_match(self::EXCLUDED_EXTENSIONS, $name) === 1;
    }

    private function __construct() {}
}
