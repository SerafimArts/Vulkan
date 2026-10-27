<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CommandPlan
{
    public function __construct(
        public CommandDefinition $command,
        public CommandKind $kind,
        /**
         * Parameters in the order of the C command
         *
         * @var list<ParameterPlan>
         */
        public array $parameters,
        /**
         * The handle, the structure or the array the command produces
         */
        public ?ParameterPlan $output,
        /**
         * Whether the command returns `VkResult`
         */
        public bool $returnsResult,
        /**
         * PHP expression of the length of the output array when it is not
         * returned by the command
         */
        public ?string $outputLength = null,
    ) {}
}
