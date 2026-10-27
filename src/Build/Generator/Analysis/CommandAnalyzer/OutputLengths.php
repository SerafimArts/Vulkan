<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis\CommandAnalyzer;

use Serafim\Vulkan\Build\Generator\Analysis\Names;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * The number of the elements of an output array that the command does not
 * return.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class OutputLengths
{
    public function __construct(
        private Registry $registry,
    ) {}

    /**
     * Returns the PHP expression of the number of the elements: the length is
     * an argument of the command or a member of a structure of the user.
     *
     * @param list<ParameterPlan> $parameters
     */
    public function find(array $parameters, ParameterPlan $output): ?string
    {
        $length = $output->field->length ?? '';

        if (\preg_match('/^(\w+)->(\w+)$/', $length, $matches) === 1) {
            foreach ($parameters as $parameter) {
                if ($parameter->role === ParameterRole::INPUT_STRUCTURE && $parameter->field->name === $matches[1]) {
                    return $this->createMemberLength($parameter, $matches[2]);
                }
            }

            return null;
        }

        foreach ($parameters as $parameter) {
            if ($parameter->field->name !== $length) {
                continue;
            }

            if ($parameter->role === ParameterRole::SCALAR) {
                return '$' . $parameter->name;
            }

            if ($parameter->role === ParameterRole::INPUT_COUNT && $parameter->counts !== null) {
                return '\count($' . $parameter->counts . ')';
            }
        }

        return null;
    }

    /**
     * Returns the number of the elements as the structure of the user knows
     * it: the length of an array of the structure is not its property.
     */
    private function createMemberLength(ParameterPlan $structure, string $member): string
    {
        $variable = '$' . $structure->name;

        foreach ($this->registry->findStruct($structure->field->type)->members ?? [] as $candidate) {
            if ($candidate->length === $member) {
                return '\count(' . $variable . '->' . Names::createVariableName($candidate->name) . ')';
            }
        }

        return $variable . '->' . Names::createVariableName($member);
    }
}
