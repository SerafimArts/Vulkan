<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Enum\VkResult;

/**
 * A command that returns nothing or the success code.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ExecuteBody implements Body
{
    public function __construct(
        private Calls $calls,
    ) {}

    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        if ($plan->returnKind !== null) {
            return $this->emitValue($namespace, $method, $plan);
        }

        if ($plan->returnsResult && \count($plan->command->successCodes) > 1) {
            $namespace->addUse(VkResult::class);
            $method->setReturnType(VkResult::class);
            $method->addComment('');
            $codes = 'VkResult::' . \implode('|VkResult::', $plan->command->successCodes);

            $method->addComment('@return ' . $codes);

            return [
                ...$this->calls->createPreparationLines($namespace, $plan),
                $this->calls->createProcedureLine($plan),
                '',
                '$result = $procedure(' . \implode(', ', $this->calls->createArguments($plan)) . ');',
                '\assert(\is_int($result));',
                '',
                '/** @var ' . $codes . ' $code */',
                '$code = $this->getResult(' . \var_export($plan->command->name, true) . ', $result);',
                '',
                'return $code;',
            ];
        }

        $method->setReturnType('void');

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            ...$this->calls->createCallLines($plan, $this->calls->createArguments($plan)),
        ];
    }

    /**
     * A command that returns a number or a flag.
     *
     * @return list<string>
     */
    private function emitValue(PhpNamespace $namespace, Method $method, CommandPlan $plan): array
    {
        $type = 'int';
        $value = '$result';

        if ($plan->returnKind === TypeKind::BOOLEAN) {
            $type = 'bool';
            $value = '$result !== 0';
        }

        $method->setReturnType($type);

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            '$result = $procedure(' . \implode(', ', $this->calls->createArguments($plan)) . ');',
            '\assert(\is_int($result));',
            '',
            'return ' . $value . ';',
        ];
    }
}
