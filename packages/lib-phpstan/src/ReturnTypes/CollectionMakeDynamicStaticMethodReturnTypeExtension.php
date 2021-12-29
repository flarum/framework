<?php

declare(strict_types=1);

namespace Flarum\PHPStan\ReturnTypes;

use Illuminate\Support\Collection;
use Flarum\PHPStan\Support\CollectionHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

class CollectionMakeDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /**
     * @var CollectionHelper
     */
    private $collectionHelper;

    public function __construct(CollectionHelper $collectionHelper)
    {
        $this->collectionHelper = $collectionHelper;
    }

    public function getClass(): string
    {
        return Collection::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'make';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        if (count($methodCall->getArgs()) < 1) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $valueType = $scope->getType($methodCall->getArgs()[0]->value);

        return $this->collectionHelper->determineGenericCollectionTypeFromType($valueType);
    }
}
