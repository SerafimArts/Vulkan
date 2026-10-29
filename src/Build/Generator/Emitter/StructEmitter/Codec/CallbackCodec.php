<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\TypeKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;

/**
 * The function that Vulkan calls: a closure of the user.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CallbackCodec extends AbstractPropertyCodec
{
    public function __construct(
        private TypeResolver $types,
    ) {}

    public function createWriteLines(PropertyContext $context): array
    {
        return $this->createNullableLines(
            $context,
            $context->createMemberExpression() . ' = $memory->toCallback('
                . \var_export($context->property->field->type, true) . ', ' . $context->createValueExpression() . ');',
        );
    }

    /**
     * Returns the signature of the function Vulkan calls: the pointers are
     * given as they are.
     */
    public function createDocType(PropertyContext $context): ?string
    {
        $pointer = $this->types->findFunctionPointer($context->property->field->type);

        if ($pointer === null) {
            return null;
        }

        $parameters = [];

        foreach ($pointer->parameters as $parameter) {
            $parameters[] = $this->createValueType($parameter->type, $parameter->pointerDepth);
        }

        $return = 'void';

        if ($pointer->returnType !== 'void' || $pointer->returnPointerDepth > 0) {
            $return = $this->createValueType($pointer->returnType, $pointer->returnPointerDepth);
        }

        if (\str_contains($return, '|')) {
            $return = '(' . $return . ')';
        }

        return '\Closure(' . \implode(', ', $parameters) . '): ' . $return;
    }

    private function createValueType(string $type, int $pointerDepth): string
    {
        if ($pointerDepth > 0) {
            return 'CData|null';
        }

        return match ($this->types->resolve($type)) {
            TypeKind::BOOLEAN => 'bool|int',
            TypeKind::FLOAT => 'float',
            TypeKind::INTEGER, TypeKind::ENUMERATION => 'int',
            default => 'CData',
        };
    }
}
