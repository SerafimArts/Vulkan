<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * Writes the marker of the structures that can be chained to a structure with
 * `pNext`. The `pNext` of the structure accepts the structures that implement
 * it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ExtensionEmitter
{
    private PhpFiles $files;

    public function __construct()
    {
        $this->files = new PhpFiles();
    }

    /**
     * Returns the name of the marker of the structure.
     *
     * @return non-empty-string
     */
    public static function createName(string $structure): string
    {
        return $structure . 'Extension';
    }

    public function emit(string $structure): GeneratedFile
    {
        $name = self::createName($structure);

        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::EXTENSION_NAMESPACE);

        $interface = $namespace->addInterface($name);

        $interface->addComment('A structure that can be chained to `' . $structure . '` with `pNext`.');
        $interface->addComment('');
        $interface->addComment('@see https://registry.khronos.org/vulkan/specs/latest/man/html/' . $structure . '.html');

        return new GeneratedFile(
            Locations::createPath('Struct/Extension', $name),
            $this->files->print($file),
        );
    }
}
