<?php

declare(strict_types=1);

namespace Flarum\PHPStan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Flarum\PHPStan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension
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

    public function getClass(): string
    {
        return EloquentBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $builderReflection = $this->reflectionProvider->getClass(EloquentBuilder::class);

        // Don't handle dynamic wheres
        if (Str::startsWith($methodReflection->getName(), 'where') &&
            ! $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        if (Str::startsWith($methodReflection->getName(), 'find') &&
            $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        if (! $templateTypeMap->getType('TModelClass') instanceof ObjectType) {
            return false;
        }

        return $builderReflection->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        /** @var Type|ObjectType|TemplateMixedType $modelType */
        $modelType = $templateTypeMap->getType('TModelClass');

        if ($modelType instanceof ObjectType && in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            $collectionClassName = $this->builderHelper->determineCollectionClassName($modelType->getClassName());

            return new GenericObjectType($collectionClassName, [$modelType]);
        }

        return $returnType;
    }
}
