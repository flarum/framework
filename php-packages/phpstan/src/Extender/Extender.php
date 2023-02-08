<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

class Extender
{
    /** @var string */
    public $qualifiedClassName;
    /** @var Expr[] */
    public $constructorArguments;
    /** @var MethodCall[] */
    public $methodCalls;

    public function __construct(string $qualifiedClassName, array $constructorArguments = [], array $methodCalls = [])
    {
        $this->qualifiedClassName = $qualifiedClassName;
        $this->constructorArguments = $constructorArguments;
        $this->methodCalls = $methodCalls;
    }

    public function isExtender(string $className): bool
    {
        return $this->qualifiedClassName === "Flarum\\Extend\\$className";
    }

    public function extends(...$args): bool
    {
        foreach ($this->constructorArguments as $index => $constructorArgument) {
            $string = null;

            switch (get_class($constructorArgument)) {
                case Expr\ClassConstFetch::class:
                    $string = $constructorArgument->class->toString();
                    break;
                case Scalar\String_::class:
                    $string = $constructorArgument->value;
                    break;
                default:
                    $string = $constructorArgument;
            }

            if ($string !== $args[$index]) {
                return false;
            }
        }

        return true;
    }

    /** @return MethodCall[] */
    public function findMethodCalls(string ...$methods): array
    {
        $methodCalls = [];

        foreach ($this->methodCalls as $methodCall) {
            if (in_array($methodCall->methodName, $methods)) {
                $methodCalls[] = $methodCall;
            }
        }

        return $methodCalls;
    }
}
