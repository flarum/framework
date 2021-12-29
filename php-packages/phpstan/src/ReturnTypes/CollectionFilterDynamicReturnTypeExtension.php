<?php

declare(strict_types=1);

namespace Flarum\PHPStan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

class CollectionFilterDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'filter';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $calledOnType = $scope->getType($methodCall->var);

        if (! $calledOnType instanceof \PHPStan\Type\Generic\GenericObjectType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $keyType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');
        $valueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TValue');

        if ($keyType === null || $valueType === null) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        if (count($methodCall->getArgs()) < 1) {
            $falseyTypes = $this->getFalseyTypes();

            $nonFalseyTypes = TypeCombinator::remove($valueType, $falseyTypes);

            if ((new ObjectType(Collection::class))->isSuperTypeOf($calledOnType)->yes()) {
                return new GenericObjectType($calledOnType->getClassName(), [$nonFalseyTypes]);
            }

            return new GenericObjectType($calledOnType->getClassName(), [$keyType, $nonFalseyTypes]);
        }

        $callbackArg = $methodCall->getArgs()[0]->value;

        $var = null;
        $expr = null;

        if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
            $statement = $callbackArg->stmts[0];
            if ($statement instanceof Return_ && $statement->expr !== null) {
                $var = $callbackArg->params[0]->var;
                $expr = $statement->expr;
            }
        } elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
            $var = $callbackArg->params[0]->var;
            $expr = $callbackArg->expr;
        }

        if ($var !== null && $expr !== null) {
            if (! $var instanceof Variable || ! is_string($var->name)) {
                throw new \PHPStan\ShouldNotHappenException();
            }

            $itemVariableName = $var->name;

            // @phpstan-ignore-next-line
            $scope = $scope->assignVariable($itemVariableName, $valueType);
            $scope = $scope->filterByTruthyValue($expr);
            $valueType = $scope->getVariableType($itemVariableName);
        }

        if ((new ObjectType(Collection::class))->isSuperTypeOf($calledOnType)->yes()) {
            return new GenericObjectType($calledOnType->getClassName(), [$valueType]);
        }

        return new GenericObjectType($calledOnType->getClassName(), [$keyType, $valueType]);
    }

    private function getFalseyTypes(): UnionType
    {
        return new UnionType([new NullType(), new ConstantBooleanType(false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType(''), new ConstantStringType('0'), new ConstantArrayType([], [])]);
    }
}
