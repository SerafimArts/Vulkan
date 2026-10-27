<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Command\Download;

use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Downloads the Vulkan headers and the registry of the Khronos repository to
 * the directory with the sources of the library.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build\Command
 */
final readonly class HeadersDownloader
{
    private HttpClientInterface $client;

    public function __construct(
        /**
         * Directory with the `include` and `registry` directories the
         * downloaded sources are placed to
         *
         * @var non-empty-string
         */
        private string $target,
        /**
         * Location of the archives of the repository, the `heads` and `tags`
         * directories live in it
         *
         * @var non-empty-string
         */
        private string $source = 'https://github.com/KhronosGroup/Vulkan-Headers/archive/refs',
        /**
         * Client the archives are requested with, the default one if
         * {@see null}
         */
        ?HttpClientInterface $client = null,
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    /**
     * Returns the location of the archive of the version, the main branch is
     * used if the version is {@see null}.
     *
     * @param non-empty-string|null $version tag of the repository, the `v`
     *        prefix of a numeric one (`1.4.366`) may be omitted
     */
    public function getArchiveUrl(?string $version = null): string
    {
        if ($version === null) {
            return $this->source . '/heads/main.zip';
        }

        if (\ctype_digit($version[0])) {
            $version = 'v' . $version;
        }

        return \sprintf('%s/tags/%s.zip', $this->source, $version);
    }

    /**
     * Replaces the Vulkan headers and the registry by the ones of the version.
     *
     * @param non-empty-string|null $version see {@see getArchiveUrl()}
     *
     * @throws InvalidHeadersException in case of the archive cannot be
     *         downloaded, extracted or placed to the target directory
     */
    public function download(?string $version = null): void
    {
        $url = $this->getArchiveUrl($version);
        $workspace = $this->target . '/.download-' . \bin2hex(\random_bytes(4));

        $this->createDirectory($workspace);

        try {
            $archive = $workspace . '/headers.zip';

            $this->fetch($url, $archive);
            $this->extract($archive, $workspace . '/extracted');

            $root = $this->findRoot($workspace . '/extracted', $archive);

            $this->replace($root . '/include/vulkan', $this->target . '/include/vulkan');
            $this->replace($root . '/include/vk_video', $this->target . '/include/vk_video');
            $this->replace($root . '/registry/vk.xml', $this->target . '/registry/vk.xml');
            $this->replace($root . '/registry/video.xml', $this->target . '/registry/video.xml');
        } finally {
            $this->removeDirectory($workspace);
        }
    }

    /**
     * @throws InvalidHeadersException in case of the archive cannot be
     *         downloaded
     */
    private function fetch(string $url, string $pathname): void
    {
        try {
            $content = $this->client->request('GET', $url)->getContent();
        } catch (ExceptionInterface $e) {
            throw InvalidHeadersException::becauseHeadersNotDownloaded($url, $e);
        }

        if (\file_put_contents($pathname, $content) === false) {
            throw InvalidHeadersException::becauseHeadersNotWritable($pathname);
        }
    }

    /**
     * @throws InvalidHeadersException in case of the archive cannot be
     *         extracted
     */
    private function extract(string $archive, string $directory): void
    {
        try {
            new \PharData($archive)->extractTo($directory);
        } catch (\Throwable $e) {
            throw InvalidHeadersException::becauseHeadersNotExtracted($archive, $e);
        }
    }

    /**
     * Returns the only directory of the extracted archive, it is named after
     * the repository and the version.
     *
     * @throws InvalidHeadersException in case of the archive has no such
     *         directory
     */
    private function findRoot(string $directory, string $archive): string
    {
        $roots = \glob($directory . '/*', \GLOB_ONLYDIR);

        if ($roots === false || \count($roots) !== 1) {
            throw InvalidHeadersException::becauseHeadersNotExtracted($archive);
        }

        return $roots[0];
    }

    /**
     * @throws InvalidHeadersException in case of the source is missing in the
     *         archive or the destination cannot be written
     */
    private function replace(string $source, string $destination): void
    {
        if (!\file_exists($source)) {
            throw InvalidHeadersException::becauseHeadersNotExtracted($source);
        }

        if (\is_dir($destination)) {
            $this->removeDirectory($destination);
        } elseif (\is_file($destination)) {
            \unlink($destination);
        }

        $this->createDirectory(\dirname($destination));

        if (!\rename($source, $destination)) {
            throw InvalidHeadersException::becauseHeadersNotWritable($destination);
        }
    }

    /**
     * @throws InvalidHeadersException in case of the directory cannot be
     *         created
     */
    private function createDirectory(string $directory): void
    {
        if (\is_dir($directory) || \mkdir($directory, recursive: true)) {
            return;
        }

        throw InvalidHeadersException::becauseHeadersNotWritable($directory);
    }

    private function removeDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $entry */
        foreach ($entries as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                \rmdir($entry->getPathname());
            } else {
                \unlink($entry->getPathname());
            }
        }

        \rmdir($directory);
    }
}
