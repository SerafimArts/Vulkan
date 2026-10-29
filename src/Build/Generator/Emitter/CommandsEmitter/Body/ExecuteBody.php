<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;

use FFI\CData;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Quirks;
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
            ...$this->createRetainLines($plan),
        ];
    }

    /**
     * The object that a command binds the memory to keeps the memory alive:
     * the first handle after the device retains the other ones.
     *
     * @return list<string>
     */
    private function createRetainLines(CommandPlan $plan): array
    {
        $writing = Quirks::findWriting($plan->command->name);

        if ($writing !== null) {
            return $this->createWritingRetainLines($plan, $writing[0], $writing[1]);
        }

        if (!Quirks::isBinding($plan->command->name)) {
            return [];
        }

        $handles = [];

        foreach ($plan->parameters as $parameter) {
            if ($parameter->role === ParameterRole::HANDLE) {
                $handles[] = $parameter;
            }
        }

        $owner = $handles[1] ?? null;

        if ($owner === null || \count($handles) < 3) {
            return [];
        }

        $lines = [''];

        foreach (\array_slice($handles, 2) as $reference) {
            $lines[] = '$' . $owner->name . '->retain($' . $reference->name . ');';
        }

        return $lines;
    }

    /**
     * The owner of a written structure keeps it alive: a descriptor set keeps
     * the resources written to it.
     *
     * @param non-empty-string $type the type of the written structures
     * @param non-empty-string $owner the member of the structure with the
     *        owner
     *
     * @return list<string>
     */
    private function createWritingRetainLines(CommandPlan $plan, string $type, string $owner): array
    {
        foreach ($plan->parameters as $parameter) {
            if ($parameter->field->type !== $type) {
                continue;
            }

            return [
                '',
                'foreach ($' . $parameter->name . ' as $write) {',
                '    $write->' . $owner . '->retain($write);',
                '}',
            ];
        }

        return [];
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

        $check = '\assert(\is_int($result));';

        if ($plan->returnKind === TypeKind::CALLBACK) {
            $namespace->addUse(CData::class);

            $type = CData::class;
            $check = '\assert($result === null || $result instanceof CData);';
            $method->setReturnNullable();
        }

        $method->setReturnType($type);

        return [
            ...$this->calls->createPreparationLines($namespace, $plan),
            $this->calls->createProcedureLine($plan),
            '',
            '$result = $procedure(' . \implode(', ', $this->calls->createArguments($plan)) . ');',
            $check,
            '',
            'return ' . $value . ';',
        ];
    }
}
