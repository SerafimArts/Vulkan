<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * Where the generated classes live.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class Locations
{
    public const string STRUCT_NAMESPACE = 'Serafim\Vulkan\Struct';

    public const string HANDLE_NAMESPACE = 'Serafim\Vulkan\Handle';

    public const string ENUM_NAMESPACE = 'Serafim\Vulkan\Enum';

    public const string EXTENSION_NAMESPACE = 'Serafim\Vulkan\Struct\Extension';

    public const string ROOT_NAMESPACE = 'Serafim\Vulkan';

    /**
     * Directories of the generated classes: the files of the Vulkan symbols
     * (the names start with `Vk`) in them are generated.
     */
    public const array DIRECTORIES = ['Struct', 'Struct/Extension', 'Handle', 'Enum'];

    private function __construct() {}

    /**
     * @return non-empty-string
     */
    public static function createClassName(string $namespace, string $name): string
    {
        return $namespace . '\\' . $name;
    }

    /**
     * @return non-empty-string
     */
    public static function createPath(string $directory, string $name): string
    {
        return $directory . '/' . $name . '.php';
    }
}
