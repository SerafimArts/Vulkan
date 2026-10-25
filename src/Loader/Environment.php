<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader;

/**
 * Environment the Vulkan library is loaded in: the operating system and the
 * variables of the process.
 */
final readonly class Environment
{
    public function __construct(
        /**
         * Operating system family, see {@see \PHP_OS_FAMILY}
         */
        public string $family,
        /**
         * Values of the environment variables by their names
         *
         * @var array<non-empty-string, string>
         */
        public array $variables = [],
    ) {}

    /**
     * Creates the environment of the current process.
     */
    public static function createFromGlobals(): self
    {
        /** @var array<non-empty-string, string> $variables */
        $variables = \getenv();

        return new self(\PHP_OS_FAMILY, $variables);
    }

    /**
     * Returns the value of the variable, or {@see null} if it is not set or
     * empty.
     *
     * @param non-empty-string $name
     */
    public function findVariable(string $name): ?string
    {
        $value = $this->variables[$name] ?? '';

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
