<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Extension\PhpStan;

use FFI\CData;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Types a member read from the native struct inside the structure class that
 * mirrors it: the type follows the property of the same name.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Extension
 */
final readonly class CDataExpressionTypeExtension implements ExpressionTypeResolverExtension
{
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (!$expr instanceof PropertyFetch || !$expr->name instanceof Identifier) {
            return null;
        }

        if (!new ObjectType(CData::class)->isSuperTypeOf($scope->getType($expr->var))->yes()) {
            return null;
        }

        if ($expr->name->name === 'cdata') {
            return new MixedType();
        }

        $class = $scope->getClassReflection();

        if ($class === null || !$class->hasNativeProperty($expr->name->name)) {
            return null;
        }

        $type = TypeCombinator::removeNull($class->getNativeProperty($expr->name->name)->getReadableType());

        $cases = TypeCombinator::remove($type, new IntegerType());

        if (!$cases instanceof NeverType && $cases->isEnum()->yes()) {
            return new IntegerType();
        }

        if ($type->isObject()->yes()) {
            if ($type->isEnum()->yes()) {
                return new IntegerType();
            }

            return new ObjectType(CData::class);
        }

        if ($type->isArray()->yes() || $type->isString()->yes()) {
            return new ObjectType(CData::class);
        }

        if ($type->isBoolean()->yes()) {
            return new IntegerType();
        }

        return $type;
    }
}
