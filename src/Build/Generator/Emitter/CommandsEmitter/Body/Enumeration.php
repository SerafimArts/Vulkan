<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

/**
 * The call of a command that fills an array of unknown length and what the
 * call returns.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Enumeration
{
    public function __construct(
        /**
         * The lines that prepare the call
         *
         * @var list<string>
         */
        public array $setup,
        /**
         * The lines of the expression that calls the command and returns the elements
         *
         * @var list<string>
         */
        public array $expression,
        /**
         * The PHP type of the value
         *
         * @var non-empty-string
         */
        public string $type,
        /**
         * The type of the value for the docblock, {@see null} if the PHP type is enough
         *
         * @var non-empty-string|null
         */
        public ?string $doc = null,
        /**
         * The marker of the structures the user can ask for in the chain of each element
         */
        public ?string $marker = null,
    ) {}
}
