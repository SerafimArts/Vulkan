<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator;

use Serafim\Vulkan\Build\Generator\Emitter\GeneratedFile;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class GenerationResult
{
    public function __construct(
        /**
         * @var list<GeneratedFile>
         */
        public array $files,
        /**
         * Reasons by the names of the commands that cannot be represented
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $skipped,
    ) {}
}
