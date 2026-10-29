<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\EnumDefinition;
use Serafim\Vulkan\Build\Generator\Registry\HandleDefinition;

/**
 * Everything that is generated and what is left out.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class GenerationPlan
{
    public function __construct(
        /**
         * @var list<CommandPlan>
         */
        public array $commands,
        /**
         * @var list<StructPlan>
         */
        public array $structs,
        /**
         * @var list<HandleDefinition>
         */
        public array $handles,
        /**
         * @var list<EnumDefinition>
         */
        public array $enums,
        /**
         * Reasons by the names of the commands that cannot be represented
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $skipped = [],
        /**
         * Names of the structures that others can be chained to
         *
         * @var list<non-empty-string>
         */
        public array $extensions = [],
        /**
         * Reasons by the names of the structures that cannot be represented
         * and that no command or chain needs
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $skippedStructs = [],
    ) {}
}
