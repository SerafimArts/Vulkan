<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader;

use FFI\Env\Exception\EnvironmentException;
use FFI\Env\Runtime;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;
use Serafim\Vulkan\Loader\Exception\InvalidRuntimeException;
use Serafim\Vulkan\Loader\Exception\LoadingException;
use Serafim\Vulkan\Loader\Headers\HeadersLoader;
use Serafim\Vulkan\Loader\Headers\HeadersLoaderInterface;
use Serafim\Vulkan\Loader\Library\LibraryLocator;
use Serafim\Vulkan\Loader\Library\LibraryLocatorInterface;
use Serafim\Vulkan\Loader\Platform\PlatformDetector;
use Serafim\Vulkan\Loader\Platform\PlatformDetectorInterface;

/**
 * Creates the {@see \FFI} instance of the Vulkan API from the library of the
 * environment and the headers of its platform.
 */
final readonly class VulkanLoader
{
    public function __construct(
        private LibraryLocatorInterface $library,
        private PlatformDetectorInterface $platform,
        private HeadersLoaderInterface $headers,
    ) {}

    /**
     * Creates the loader that adapts to the environment it runs in.
     */
    public static function createDefault(): self
    {
        return new self(
            library: LibraryLocator::createDefault(),
            platform: PlatformDetector::createDefault(),
            headers: new HeadersLoader(),
        );
    }

    /**
     * Returns the copy of the loader that looks for the library by the given
     * locator.
     *
     * @api
     */
    public function withLibraryLocator(LibraryLocatorInterface $locator): self
    {
        return new self($locator, $this->platform, $this->headers);
    }

    /**
     * Returns the copy of the loader that detects the platform by the given
     * detector.
     *
     * @api
     */
    public function withPlatformDetector(PlatformDetectorInterface $detector): self
    {
        return new self($this->library, $detector, $this->headers);
    }

    /**
     * Returns the copy of the loader that reads the headers by the given
     * loader.
     *
     * @api
     */
    public function withHeadersLoader(HeadersLoaderInterface $loader): self
    {
        return new self($this->library, $this->platform, $loader);
    }

    /**
     * Creates the {@see \FFI} instance of the Vulkan API.
     *
     * @param Environment|null $environment {@see null} for the environment
     *        of the current process
     *
     * @throws InvalidRuntimeException in case of `ext-ffi` is not available
     * @throws InvalidLibraryException in case of the library cannot be found
     * @throws InvalidPlatformException in case of the platform cannot be
     *         detected
     * @throws InvalidHeadersException in case of the headers cannot be
     *         read
     * @throws LoadingException in case of the library cannot be bound to the
     *         headers
     */
    public function load(?Environment $environment = null): \FFI
    {
        try {
            Runtime::assertAvailable();
        } catch (EnvironmentException $e) {
            throw InvalidRuntimeException::becauseExtensionIsNotAvailable($e);
        }

        $environment ??= Environment::createFromGlobals();

        $library = $this->library->getLibrary($environment);
        $platform = $this->platform->getPlatform($environment);
        $headers = $this->headers->getHeaders($platform);

        try {
            return \FFI::cdef($headers, $library);
        } catch (\FFI\Exception|\FFI\ParserException $e) {
            throw LoadingException::becauseFfiNotCreated($library, $e);
        }
    }
}
