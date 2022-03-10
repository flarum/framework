<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\ReturnTypes\Helpers;

use Flarum\PHPStan\Concerns\HasContainer;
use Illuminate\Foundation\Application;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

class AppExtension implements DynamicFunctionReturnTypeExtension
{
    use HasContainer;

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'app' || $functionReflection->getName() === 'resolve';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->getArgs()) === 0) {
            return new ObjectType(Application::class);
        }

        /** @var Expr $expr */
        $expr = $functionCall->getArgs()[0]->value;

        if ($expr instanceof String_) {
            try {
                /** @var object|null $resolved */
                $resolved = $this->resolve($expr->value);

                if ($resolved === null) {
                    return new ErrorType();
                }

                return new ObjectType(get_class($resolved));
            } catch (Throwable $exception) {
                return new ErrorType();
            }
        }

        if ($expr instanceof ClassConstFetch && $expr->class instanceof FullyQualified) {
            return new ObjectType($expr->class->toString());
        }

        return new NeverType();
    }
}
