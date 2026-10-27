<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class GeneratedFile
{
    public function __construct(
        /**
         * Path relative to the `src` directory
         *
         * @var non-empty-string
         */
        public string $path,
        public string $contents,
    ) {}
}
