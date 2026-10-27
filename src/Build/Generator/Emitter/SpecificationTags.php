<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use Serafim\Vulkan\Build\Generator\Registry\Deprecation;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * The tags of a docblock that tell where a symbol of the specification
 * comes from: the page of the specification, the version and the
 * deprecation. Legacy functionality is fully supported, so it is not tagged
 * as deprecated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class SpecificationTags
{
    private const string MANUAL = 'https://registry.khronos.org/vulkan/specs/latest/man/html/';

    private const string LEGACY = 'https://registry.khronos.org/vulkan/specs/latest/appendices/legacy.html#';

    public function __construct(
        private Registry $registry,
    ) {}

    /**
     * Returns the lines of the description: legacy functionality is told to be
     * so.
     *
     * @return list<string>
     */
    public function createDescription(string $name): array
    {
        $deprecation = $this->registry->findDeprecation($name);

        if ($deprecation === null || !$deprecation->isLegacy) {
            return [];
        }

        if ($deprecation->supersededBy === null) {
            return ['Legacy functionality.'];
        }

        return ['Legacy functionality, superseded by `' . $deprecation->supersededBy . '`.'];
    }

    /**
     * @return list<string>
     */
    public function createLines(string $name): array
    {
        $lines = ['@see ' . self::MANUAL . $name . '.html'];
        $link = $this->registry->findDeprecation($name)?->link;

        if ($link !== null) {
            $lines[] = '@see ' . self::LEGACY . $link;
        }

        $since = $this->registry->findSince($name);

        if ($since !== null) {
            $lines[] = '@since ' . $since;
        }

        $deprecation = $this->registry->findDeprecation($name);

        if ($deprecation !== null && ($deprecation->isLegacy === false || \str_starts_with($name, 'vk'))) {
            $lines[] = '@deprecated ' . $this->createReason($deprecation);
        }

        return $lines;
    }

    private function createReason(Deprecation $deprecation): string
    {
        if (!$deprecation->isLegacy) {
            return $deprecation->description;
        }

        if ($deprecation->supersededBy === null) {
            return 'legacy functionality';
        }

        if (\str_starts_with($deprecation->supersededBy, 'vk')) {
            return 'legacy functionality, use {@see self::' . $deprecation->supersededBy . '()} instead';
        }

        return 'legacy functionality, use `' . $deprecation->supersededBy . '` instead';
    }

    /**
     * Returns the reason a symbol is deprecated for, {@see null} if it is not
     * deprecated or is legacy.
     */
    public function findDeprecation(string $name): ?Deprecation
    {
        $deprecation = $this->registry->findDeprecation($name);

        if ($deprecation === null || $deprecation->isLegacy) {
            return null;
        }

        return $deprecation;
    }
}
