<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\ReturnTypes;

use Flarum\PHPStan\Methods\BuilderHelper;
use Flarum\PHPStan\Methods\ModelTypeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class BuilderModelFindExtension implements DynamicMethodReturnTypeExtension
{
    /** @var BuilderHelper */
    private $builderHelper;

    /** @var ReflectionProvider */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider, BuilderHelper $builderHelper)
    {
        $this->builderHelper = $builderHelper;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Builder::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        if (! Str::startsWith($methodName, 'find')) {
            return false;
        }

        $model = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModelClass');

        if ($model === null || ! $model instanceof ObjectType) {
            return false;
        }

        if (! $this->reflectionProvider->getClass(Builder::class)->hasNativeMethod($methodName) &&
            ! $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodName)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        /** @var ObjectType $model */
        $model = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModelClass');
        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        $argType = $scope->getType($methodCall->getArgs()[0]->value);

        $returnType = ModelTypeHelper::replaceStaticTypeWithModel($returnType, $model->getClassName());

        if ($argType->isIterable()->yes()) {
            if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
                $collectionClassName = $this->builderHelper->determineCollectionClassName($model->getClassName());

                return new GenericObjectType($collectionClassName, [$model]);
            }

            return TypeCombinator::remove($returnType, $model);
        }

        if ($argType instanceof MixedType) {
            return $returnType;
        }

        return TypeCombinator::remove(
            TypeCombinator::remove(
                $returnType,
                new ArrayType(new MixedType(), $model)
            ),
            new ObjectType(Collection::class)
        );
    }
}
