<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterPlan;
use Serafim\Vulkan\Build\Generator\Analysis\ParameterRole;
use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Quirks;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Creates the handle of a command that creates handles. The handle destroys
 * the native object, a handle that Vulkan owns is not destroyed.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleFactory
{
    public function __construct(
        private Registry $registry,
        private Calls $calls,
    ) {}

    /**
     * Returns the lines that prepare the closure of a handle and the
     * expression that creates the handle.
     *
     * @param string $native the expression of the native handle
     * @param non-empty-string|null $type C type of the handle when it is not
     *        the type of the output
     *
     * @return array{list<string>, string}
     * @throws UnsupportedDeclarationException in case of the destroyed handle
     *         needs an argument the command does not have
     */
    public function create(
        CommandPlan $plan,
        ParameterPlan $output,
        string $class,
        ?ParameterPlan $parent,
        string $native,
        ?string $type = null,
    ): array {
        $type ??= $output->field->type;
        $destroy = $this->registry->findReleaseCommand($type);
        $creation = 'new ' . $class . '($this, ' . $native . ', ' . $this->calls->createParentExpression($parent);
        $references = $this->createReferences($plan);

        if ($destroy === null) {
            if ($references === null) {
                return [[], $creation . ')'];
            }

            return [[], $creation . ', references: ' . $references . ')'];
        }

        $lines = [];
        $uses = [];
        $arguments = [];
        $isResolved = $parent !== null;

        if ($isResolved) {
            $lines[] = '$destroy = $this->vkGetInstanceProc(' . \var_export($destroy->name, true) . ', $'
                . $parent->name . ');';
            $uses[] = '$destroy';
        }

        foreach ($destroy->parameters as $parameter) {
            if ($parameter->type === $type && $parameter->pointerDepth === 0) {
                $arguments[] = '$handle->pointer';

                continue;
            }

            if ($parameter->type === Quirks::ALLOCATOR_TYPE) {
                $arguments[] = 'null';

                continue;
            }

            $owner = $this->calls->findHandleParameter($plan, $parameter->type);

            if ($owner === null) {
                throw UnsupportedDeclarationException::becauseDeclarationIsNotSupported(
                    $destroy->name,
                    'the parameter ' . $parameter->name . ' is not available to the destroyed handle',
                );
            }

            $lines[] = '$' . $owner->name . 'Pointer = $' . $owner->name . '->pointer;';
            $uses[] = '$' . $owner->name . 'Pointer';
            $arguments[] = '$' . $owner->name . 'Pointer';
        }

        $use = '';

        if ($uses !== []) {
            $use = ' use (' . \implode(', ', $uses) . ')';
        }

        if ($lines !== []) {
            $lines[] = '';
        }

        $expression = $creation . ', function (Handle $handle)' . $use . ": void {\n";

        if (!$isResolved) {
            $expression .= '    $destroy = $this->vkGetInstanceProc(' . \var_export($destroy->name, true)
                . ", \$handle);\n";
        }

        $expression .= '    $destroy(' . \implode(', ', $arguments) . ");\n}";

        if ($references !== null) {
            $expression .= ', ' . $references;
        }

        return [$lines, $expression . ')'];
    }

    /**
     * Returns the list of the objects that the created handle refers to: the
     * structures and the arrays the user has given to the command.
     */
    private function createReferences(CommandPlan $plan): ?string
    {
        $variables = [];

        foreach ($plan->parameters as $parameter) {
            $isReference = $parameter->role === ParameterRole::INPUT_STRUCTURE
                || ($parameter->role === ParameterRole::INPUT_ARRAY && $parameter->kind === TypeKind::STRUCTURE);

            if ($isReference) {
                $variables[] = '$' . $parameter->name;
            }
        }

        if ($variables === []) {
            return null;
        }

        return '[' . \implode(', ', $variables) . ']';
    }
}
