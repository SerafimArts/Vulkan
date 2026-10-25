<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Library;

use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Library\LibraryDriver\FamilyLibraryDriver;

/**
 * Finds the Vulkan shared library by asking the drivers in order of their
 * priority.
 */
final readonly class LibraryLocator implements LibraryLocatorInterface
{
    public function __construct(
        /**
         * Drivers in order of their priority
         *
         * @var list<LibraryDriverInterface>
         */
        private array $drivers,
    ) {}

    /**
     * Creates the locator with the drivers of all the operating systems
     * known to the library.
     */
    public static function createDefault(): self
    {
        return new self([
            new FamilyLibraryDriver('Windows', ['vulkan.dll', 'vulkan-1.dll']),
            new FamilyLibraryDriver('Linux', ['libvulkan.so', 'libvulkan.so.1']),
            new FamilyLibraryDriver('BSD', ['libvulkan.so', 'libvulkan.so.1']),
            new FamilyLibraryDriver('Solaris', ['libvulkan.so', 'libvulkan.so.1']),
            new FamilyLibraryDriver('Darwin', [
                'libvulkan.dylib',
                'libvulkan.1.dylib',
                'libMoltenVK.dylib',
            ]),
        ]);
    }

    /**
     * Returns the copy of the locator that asks the given driver before all
     * the others, which allows overriding the behaviour of a default driver
     * or to support another operating system.
     *
     * @api
     */
    public function withDriver(LibraryDriverInterface $driver): self
    {
        return new self([$driver, ...$this->drivers]);
    }

    /**
     * Returns the pathname found by the first driver that supports the
     * environment and knows where the library is.
     */
    public function getLibrary(?Environment $environment = null): string
    {
        $environment ??= Environment::createFromGlobals();
        $isSupported = false;

        foreach ($this->drivers as $driver) {
            if (!$driver->supports($environment)) {
                continue;
            }

            $isSupported = true;

            $pathname = $driver->resolve($environment);

            if ($pathname !== null) {
                return $pathname;
            }
        }

        if ($isSupported) {
            throw InvalidLibraryException::becauseLibraryNotFound($environment->family);
        }

        throw InvalidLibraryException::becauseUnsupportedFamily($environment->family);
    }
}
