<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class ModelFactoryDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Model::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        if ($methodReflection->getName() !== 'factory') {
            return false;
        }

        // Class only available on Laravel 8
        if (! class_exists('\Illuminate\Database\Eloquent\Factories\Factory')) {
            return false;
        }

        return true;
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $class = $methodCall->class;

        if (! $class instanceof Name) {
            return new ErrorType();
        }

        $modelName = basename(str_replace('\\', '/', $class->toCodeString()));

        if (! class_exists('Database\\Factories\\'.$modelName.'Factory')) {
            return new ErrorType();
        }

        return new ObjectType('Database\\Factories\\'.$modelName.'Factory');
    }
}
