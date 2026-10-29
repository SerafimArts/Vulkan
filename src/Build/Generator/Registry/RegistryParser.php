<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

use Serafim\Vulkan\Build\Generator\Exception\InvalidRegistryException;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\CommandParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\ConstantParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\Elements;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\EnumParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\FieldParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\FunctionPointerParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\HandleParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\RequirementParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\StructParser;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser\TypeParser;

/**
 * Reads `vk.xml`. The definitions of Vulkan SC and of the disabled
 * extensions are skipped.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class RegistryParser
{
    /**
     * @param non-empty-string $pathname
     * @param array<string, true> $declared the types the headers declare
     *
     * @throws InvalidRegistryException in case of the file cannot be read
     */
    public function parse(string $pathname, array $declared = []): Registry
    {
        $elements = new Elements(new \DOMXPath($this->load($pathname)));
        $types = new TypeParser($elements);
        $fields = new FieldParser($elements, $types->parseTypeAliases());
        $requirements = new RequirementParser($elements);

        $commands = new CommandParser($elements, $fields);

        return new Registry(
            structs: new StructParser($elements, $fields)->parse(),
            commands: $commands->parse(),
            handles: new HandleParser($elements)->parse(),
            enums: new EnumParser($elements)->parse(),
            bitmasks: $types->parseBitmasks(),
            baseTypes: $types->parseBaseTypes(),
            flagBits: $types->parseFlagBits(),
            constants: $types->parseConstants(),
            since: $requirements->parseSince(),
            deprecations: $requirements->parseDeprecations(),
            commandAliases: $commands->parseAliases(),
            nativeTypes: $types->parseNativeTypes(),
            functionPointers: new FunctionPointerParser($elements, $fields)->parse(),
            declared: $declared,
            defines: new ConstantParser($elements)->parse(),
            excluded: $requirements->parseExcluded(),
        );
    }

    /**
     * @param non-empty-string $pathname
     *
     * @throws InvalidRegistryException in case of the file cannot be read
     */
    private function load(string $pathname): \DOMDocument
    {
        $previous = \libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $isLoaded = $document->load($pathname);

        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);

        if (!$isLoaded) {
            throw InvalidRegistryException::becauseRegistryNotReadable($pathname);
        }

        return $document;
    }
}
