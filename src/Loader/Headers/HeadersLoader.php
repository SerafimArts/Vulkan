<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Headers;

use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Platform\Platform;

/**
 * Reads the Vulkan headers of a platform that were compiled in advance.
 *
 * @see PreprocessorHeadersLoader
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
     * Returns the name of the file with the headers compiled for the platform.
     */
    public static function getFilename(Platform $platform): string
    {
        return \sprintf('vulkan-%s.h', \strtolower($platform->name));
    }

    public function getHeaders(Platform $platform): string
    {
        $pathname = $this->directory . '/' . self::getFilename($platform);
        $headers = false;

        if (\is_readable($pathname)) {
            $headers = \file_get_contents($pathname);
        }

        if ($headers === false) {
            throw InvalidHeadersException::becauseHeadersNotReadable($pathname);
        }

        return $headers;
    }
}
