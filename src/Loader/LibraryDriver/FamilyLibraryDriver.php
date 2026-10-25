<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\LibraryDriver;

use FFI\Location\Locator;
use Serafim\Vulkan\Loader\LibraryDriverInterface;

/**
 * Driver of a single operating system family that looks for the library
 * by its well-known names.
 */
abstract class FamilyLibraryDriver implements LibraryDriverInterface
{
    /**
     * Operating system family the driver serves, see {@see \PHP_OS_FAMILY}
     *
     * @var non-empty-string
     */
    abstract protected string $family { get; }

    /**
     * Names under which the library may be installed; the most preferred one
     * goes first
     *
     * @var non-empty-list<non-empty-string>
     */
    abstract protected array $names { get; }

    public function supports(string $family): bool
    {
        return $family === $this->family;
    }

    public function resolve(): ?string
    {
        return Locator::resolve(...$this->names);
    }
}
