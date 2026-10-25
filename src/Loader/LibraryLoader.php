<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader;

use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\LibraryDriver\BsdLibraryDriver;
use Serafim\Vulkan\Loader\LibraryDriver\DarwinLibraryDriver;
use Serafim\Vulkan\Loader\LibraryDriver\LinuxLibraryDriver;
use Serafim\Vulkan\Loader\LibraryDriver\SolarisLibraryDriver;
use Serafim\Vulkan\Loader\LibraryDriver\WindowsLibraryDriver;

/**
 * Finds the Vulkan shared library of the operating system by asking the
 * drivers in order of their priority.
 */
final readonly class LibraryLoader
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
     * Creates the loader with the drivers of all the operating systems known
     * to the library.
     */
    public static function createDefault(): self
    {
        return new self([
            new WindowsLibraryDriver(),
            new LinuxLibraryDriver(),
            new BsdLibraryDriver(),
            new SolarisLibraryDriver(),
            new DarwinLibraryDriver(),
        ]);
    }

    /**
     * Returns the copy of the loader that asks the given driver before all
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
     * Returns the pathname of the library found by the first driver that
     * supports the operating system and knows where the library is.
     *
     * @param string $family Operating system family, see {@see \PHP_OS_FAMILY}
     * @return non-empty-string
     * @throws InvalidLibraryException in case of no driver supports the
     *         operating system or the library is not installed
     */
    public function getLibrary(string $family = \PHP_OS_FAMILY): string
    {
        $isSupported = false;

        foreach ($this->drivers as $driver) {
            if (!$driver->supports($family)) {
                continue;
            }

            $isSupported = true;

            if (($pathname = $driver->resolve()) !== null) {
                return $pathname;
            }
        }

        // Supported but not found
        if ($isSupported) {
            throw InvalidLibraryException::becauseLibraryNotFound('Vulkan library for ' . $family);
        }

        throw InvalidLibraryException::becauseUnsupportedPlatform($family);
    }
}
