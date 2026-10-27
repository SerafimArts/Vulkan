<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

/**
 * An output array whose length is known: how it is allocated and read.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class QueryArray
{
    public function __construct(
        /**
         * The lines that allocate the native array before the call
         *
         * @var list<string>
         */
        public array $allocate,
        /**
         * The lines that read the elements after the call
         *
         * @var list<string>
         */
        public array $read,
        /**
         * The variable that holds the value the lines make
         */
        public string $variable,
        /**
         * The PHP type of the value
         *
         * @var non-empty-string
         */
        public string $type,
        /**
         * The type of the value for the docblock, {@see null} if the PHP type is enough
         */
        public ?string $doc = null,
    ) {}
}
