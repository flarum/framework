<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\ReturnTypes\Helpers;

use Flarum\PHPStan\Support\CollectionHelper;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

final class CollectExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * @var CollectionHelper
     */
    private $collectionHelper;

    public function __construct(CollectionHelper $collectionHelper)
    {
        $this->collectionHelper = $collectionHelper;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'collect';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->getArgs()) < 1) {
            return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        }

        $valueType = $scope->getType($functionCall->getArgs()[0]->value);

        return $this->collectionHelper->determineGenericCollectionTypeFromType($valueType);
    }
}
