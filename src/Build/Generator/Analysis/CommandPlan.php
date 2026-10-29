<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\CommandDefinition;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;

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
        /**
         * What a command that returns a number or a flag, not `VkResult`, is
         * returning
         */
        public ?TypeKind $returnKind = null,
        /**
         * All the outputs of a command that fills several
         *
         * @var list<ParameterPlan>
         */
        public array $outputs = [],
        /**
         * PHP expressions of the lengths of the output arrays by the names of
         * the parameters, for a command that fills several outputs
         *
         * @var array<string, string>
         */
        public array $outputLengths = [],
        /**
         * The member of the output structure that holds the created handles
         */
        public ?FieldDefinition $handles = null,
        /**
         * The name of the parameter with the number of the structures and the
         * name of their member that the user gives, for a command that fills
         * the structures the user fills in part
         *
         * @var array{non-empty-string, non-empty-string}|null
         */
        public ?array $requested = null,
    ) {}

    /**
     * Whether the array is filled in the two calls of the command: the first
     * one returns the length, the second one fills the array.
     */
    public function isEnumerated(ParameterPlan $array): bool
    {
        $isArray = $array->role === ParameterRole::OUTPUT_ARRAY || $array->role === ParameterRole::OUTPUT_BINARY;

        if (!$isArray) {
            return false;
        }

        foreach ($this->parameters as $parameter) {
            if ($parameter->role === ParameterRole::OUTPUT_COUNT && $parameter->field->name === $array->field->length) {
                return true;
            }
        }

        return false;
    }
}
