<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Headers;

use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Platform\Platform;

/**
 * Reads the Vulkan headers of a platform that were compiled in advance: the
 * declarations that all the platforms share, followed by the ones of the
 * platform.
 */
final readonly class HeadersLoader implements HeadersLoaderInterface
{
    public function __construct(
        /**
         * Directory with the compiled headers
         *
         * @var non-empty-string
         */
        private string $directory = __DIR__ . '/../../../resources/headers',
    ) {}

    /**
     * Returns the name of the file with the declarations that all the
     * platforms share.
     */
    public static function getCoreFilename(): string
    {
        return 'vulkan.h';
    }

    /**
     * Returns the name of the file with the declarations of the platform.
     */
    public static function getFilename(Platform $platform): string
    {
        return \sprintf('vulkan-%s.h', \strtolower($platform->name));
    }

    public function getHeaders(Platform $platform): string
    {
        return $this->read(self::getCoreFilename()) . $this->read(self::getFilename($platform));
    }

    /**
     * @throws InvalidHeadersException in case of the file cannot be read
     */
    private function read(string $filename): string
    {
        $pathname = $this->directory . '/' . $filename;
        $contents = false;

        if (\is_readable($pathname)) {
            $contents = \file_get_contents($pathname);
        }

        if ($contents === false) {
            throw InvalidHeadersException::becauseHeadersNotReadable($pathname);
        }

        return $contents;
    }
}
