<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry\RegistryParser;

use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CommandParser
{
    public function __construct(
        private Elements $elements,
        private FieldParser $fields,
    ) {}

    /**
     * @return array<non-empty-string, CommandDefinition>
     */
    public function parse(): array
    {
        $commands = [];

        foreach ($this->elements->select('//commands/command[proto]') as $element) {
            if ($element->hasAttribute('alias') || !$this->elements->isSupported($element)) {
                continue;
            }

            $proto = $this->elements->select('proto', $element)[0];
            $name = $this->elements->readChildText($proto, 'name');
            $returnType = $this->elements->readChildText($proto, 'type');

            if ($name === '' || $returnType === '') {
                continue;
            }

            $parameters = [];

            foreach ($this->elements->select('param', $element) as $parameter) {
                if ($this->elements->isSupported($parameter)) {
                    $parameters[] = $this->fields->parse($parameter);
                }
            }

            $commands[$name] = new CommandDefinition(
                $name,
                $returnType,
                $parameters,
                $this->parseCodes($element->getAttribute('successcodes')),
                $this->parseCodes($element->getAttribute('errorcodes')),
                $this->parseCodes($element->getAttribute('queues')),
                $this->elements->createNullable($element->getAttribute('renderpass')),
                $this->parseCodes($element->getAttribute('cmdbufferlevel')),
            );
        }

        return $commands;
    }

    /**
     * Returns the names of the commands by the names of their aliases.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function parseAliases(): array
    {
        $aliases = [];

        foreach ($this->elements->select('//commands/command[@alias]') as $element) {
            $name = $this->elements->createNullable($element->getAttribute('name'));
            $command = $this->elements->createNullable($element->getAttribute('alias'));

            if ($name !== null && $command !== null && $this->elements->isSupported($element)) {
                $aliases[$name] = $command;
            }
        }

        return $aliases;
    }

    /**
     * @return list<non-empty-string>
     */
    private function parseCodes(string $codes): array
    {
        $names = [];

        foreach (\explode(',', $codes) as $code) {
            $name = $this->elements->createNullable(\trim($code));

            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
