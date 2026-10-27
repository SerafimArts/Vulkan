<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator;

use Serafim\Vulkan\Build\Generator\Emitter\GeneratedFile;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Exception\InvalidOutputException;

/**
 * Writes the generated files and removes the generated files that are no
 * longer needed.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FilesWriter
{
    public function __construct(
        /**
         * The `src` directory
         *
         * @var non-empty-string
         */
        private string $directory,
    ) {}

    /**
     * @param list<GeneratedFile> $files
     *
     * @throws InvalidOutputException in case of a file cannot be written
     */
    public function write(array $files): void
    {
        $this->removeStale($files);

        foreach ($files as $file) {
            $pathname = $this->directory . '/' . $file->path;

            if (!\is_dir(\dirname($pathname)) && !\mkdir(\dirname($pathname), recursive: true)) {
                throw InvalidOutputException::becauseFileNotWritable(\dirname($pathname));
            }

            if (\file_put_contents($pathname, $file->contents) === false) {
                throw InvalidOutputException::becauseFileNotWritable($pathname);
            }
        }
    }

    /**
     * @param list<GeneratedFile> $files
     */
    private function removeStale(array $files): void
    {
        $current = [];

        foreach ($files as $file) {
            $current[$file->path] = true;
        }

        foreach (Locations::DIRECTORIES as $directory) {
            $pathnames = \glob($this->directory . '/' . $directory . '/Vk*.php');

            if ($pathnames === false) {
                continue;
            }

            foreach ($pathnames as $pathname) {
                if (!isset($current[$directory . '/' . \basename($pathname)])) {
                    \unlink($pathname);
                }
            }
        }
    }
}
