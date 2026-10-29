<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Quirks;
use Serafim\Vulkan\Build\Generator\Registry\Deprecation;

/**
 * What the versions and the extensions of the specification provide, and what
 * of it is deprecated.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class RequirementParser
{
    private const string PROVIDED = './require/command | ./require/type';

    public function __construct(
        private Elements $elements,
    ) {}

    /**
     * Returns where a command or a type appeared: the version of Vulkan, or
     * the extensions that provide it when it is not a part of a version.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function parseSince(): array
    {
        $since = [];

        foreach ($this->elements->select('//feature') as $feature) {
            $number = $feature->getAttribute('number');

            if ($number === '' || !$this->isVulkan($feature->getAttribute('api'))) {
                continue;
            }

            foreach ($this->selectProvided($feature) as $name) {
                $since[$name] ??= $number;
            }
        }

        foreach ($this->findExtensionProviders() as $name => $extensions) {
            if (!isset($since[$name])) {
                $since[$name] = \implode(', ', $extensions);
            }
        }

        /** @var array<non-empty-string, non-empty-string> $since */
        return $since;
    }

    /**
     * Returns the commands and the types that only the platforms other than
     * Windows, Linux and macOS, Vulkan SC or the beta extensions provide.
     *
     * @return array<non-empty-string, true>
     */
    public function parseExcluded(): array
    {
        $excluded = [];

        foreach ($this->elements->select('//extensions/extension') as $extension) {
            if (!$this->isExcludedExtension($extension)) {
                continue;
            }

            foreach ($this->elements->select(self::PROVIDED, $extension) as $item) {
                $excluded[$item->getAttribute('name')] = true;
            }
        }

        foreach ($this->elements->select('//require/*[@api="vulkansc"]') as $item) {
            $excluded[$item->getAttribute('name')] = true;
        }

        foreach ($this->elements->select('//feature') as $feature) {
            if ($this->isVulkan($feature->getAttribute('api'))) {
                continue;
            }

            foreach ($this->elements->select(self::PROVIDED, $feature) as $item) {
                $excluded[$item->getAttribute('name')] = true;
            }
        }

        foreach ($this->findExtensionProviders() as $name => $extensions) {
            $providers = \array_filter(
                $extensions,
                fn(string $extension): bool => !$this->isExcludedExtensionName($extension),
            );

            if ($providers !== []) {
                unset($excluded[$name]);
            }
        }

        foreach ($this->parseCoreNames() as $name => $_) {
            unset($excluded[$name]);
        }

        /** @var array<non-empty-string, true> $excluded */
        return $excluded;
    }

    private function isExcludedExtensionName(string $name): bool
    {
        $extension = $this->elements->select('//extensions/extension[@name="' . $name . '"]')[0] ?? null;

        return $extension === null || $this->isExcludedExtension($extension);
    }

    private function isExcludedExtension(\DOMElement $extension): bool
    {
        return Quirks::isExcludedExtension($extension->getAttribute('name'), $extension->getAttribute('platform'))
            || !$this->isVulkan($extension->getAttribute('supported'));
    }

    /**
     * @return array<non-empty-string, Deprecation>
     */
    public function parseDeprecations(): array
    {
        $deprecations = [];

        foreach ($this->elements->select('//feature | //extensions/extension') as $element) {
            foreach ($this->elements->select('./deprecate', $element) as $deprecate) {
                $link = $this->elements->createNullable($deprecate->getAttribute('explanationlink'));

                foreach ($this->elements->select('./command | ./type', $deprecate) as $item) {
                    $name = $this->elements->createNullable($item->getAttribute('name'));
                    $replacement = $this->elements->createNullable($item->getAttribute('supersededby'));

                    if ($name === null) {
                        continue;
                    }

                    $reason = 'legacy';

                    if ($replacement !== null) {
                        $reason = 'superseded by ' . $replacement;
                    }

                    $deprecations[$name] = new Deprecation($reason, $replacement, $link, true);
                }
            }
        }

        return $deprecations + $this->parseDeprecatedExtensions();
    }

    /**
     * What is provided only by the extensions that are deprecated.
     *
     * @return array<non-empty-string, Deprecation>
     */
    private function parseDeprecatedExtensions(): array
    {
        $core = $this->parseCoreNames();
        $deprecated = [];
        $replacements = [];

        foreach ($this->elements->select('//extensions/extension') as $extension) {
            if ($extension->hasAttribute('deprecatedby')) {
                $name = $extension->getAttribute('name');

                $deprecated[$name] = true;
                $replacements[$name] = $extension->getAttribute('deprecatedby');
            }
        }

        $deprecations = [];

        foreach ($this->findExtensionProviders() as $name => $extensions) {
            if (isset($core[$name])) {
                continue;
            }

            $isDeprecated = true;

            foreach ($extensions as $extension) {
                $isDeprecated = $isDeprecated && isset($deprecated[$extension]);
            }

            if ($isDeprecated) {
                $deprecations[$name] = new Deprecation($this->createExtensionReason($extensions, $replacements));
            }
        }

        return $deprecations;
    }

    /**
     * @param list<non-empty-string> $extensions
     * @param array<string, string> $replacements the extensions that replace
     *        the deprecated ones
     *
     * @return non-empty-string
     */
    private function createExtensionReason(array $extensions, array $replacements): string
    {
        $replacement = $replacements[$extensions[0]] ?? '';

        if ($replacement === '') {
            return 'the extension ' . $extensions[0] . ' is deprecated';
        }

        return 'the extension ' . $extensions[0] . ' is deprecated by ' . $replacement;
    }

    /**
     * @return array<non-empty-string, true>
     */
    private function parseCoreNames(): array
    {
        $core = [];

        foreach ($this->elements->select('//feature') as $feature) {
            if (!$this->isVulkan($feature->getAttribute('api'))) {
                continue;
            }

            foreach ($this->selectProvided($feature) as $name) {
                $core[$name] = true;
            }
        }

        return $core;
    }

    /**
     * @return array<non-empty-string, list<non-empty-string>> the extensions
     *         by the names of the commands and the types they provide
     */
    private function findExtensionProviders(): array
    {
        $providers = [];

        foreach ($this->elements->select('//extensions/extension') as $extension) {
            $name = $this->elements->createNullable($extension->getAttribute('name'));

            if ($name === null || !$this->isVulkan($extension->getAttribute('supported'))) {
                continue;
            }

            foreach ($this->selectProvided($extension) as $provided) {
                $providers[$provided][] = $name;
            }
        }

        return $providers;
    }

    /**
     * @return list<non-empty-string> the names of the commands and the types
     *         the element requires
     */
    private function selectProvided(\DOMElement $element): array
    {
        $names = [];

        foreach ($this->elements->select(self::PROVIDED, $element) as $item) {
            if ($item->getAttribute('api') === 'vulkansc') {
                continue;
            }

            $name = $this->elements->createNullable($item->getAttribute('name'));

            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function isVulkan(string $api): bool
    {
        return \in_array('vulkan', \explode(',', $api), true);
    }
}
