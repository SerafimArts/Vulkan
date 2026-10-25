<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Library\LibraryDriver;

use FFI\Location\Locator;
use Serafim\Vulkan\Loader\Environment;
use Serafim\Vulkan\Loader\Library\LibraryDriverInterface;

/**
 * Looks for the library of an operating system family by its well-known
 * names.
 */
final readonly class FamilyLibraryDriver implements LibraryDriverInterface
{
    public function __construct(
        /**
         * Operating system family the driver serves, see
         * {@see \PHP_OS_FAMILY}
         *
         * @var non-empty-string
         */
        private string $family,
        /**
         * Names under which the library may be installed, the most preferred
         * one goes first
         *
         * @var non-empty-list<non-empty-string>
         */
        private array $names,
    ) {}

    public function supports(Environment $environment): bool
    {
        return $environment->family === $this->family;
    }

    public function resolve(Environment $environment): ?string
    {
        return Locator::resolve(...$this->names);
    }
}
