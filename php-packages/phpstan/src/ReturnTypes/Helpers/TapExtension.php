<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\ReturnTypes\Helpers;

use Illuminate\Support\HigherOrderTapProxy;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;

class TapExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'tap';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->getArgs()) === 1) {
            $type = $scope->getType($functionCall->getArgs()[0]->value);

            return new GenericObjectType(HigherOrderTapProxy::class, [
                $type instanceof ThisType ? $type->getStaticObjectType() : $type,
            ]);
        }

        if (count($functionCall->getArgs()) === 2) {
            return $scope->getType($functionCall->getArgs()[0]->value);
        }

        return new NeverType();
    }
}
