<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Relations;

use Flarum\PHPStan\Extender\MethodCall;
use Flarum\PHPStan\Extender\Resolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

class ModelRelationsExtension implements MethodsClassReflectionExtension, PropertiesClassReflectionExtension
{
    /** @var Resolver */
    private $extendersResolver;

    public function __construct(Resolver $extendersResolver)
    {
        $this->extendersResolver = $extendersResolver;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->findRelationMethod($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->resolveRelationMethod($this->findRelationMethod($classReflection, $methodName), $classReflection);
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->findRelationMethod($classReflection, $propertyName) !== null;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): \PHPStan\Reflection\PropertyReflection
    {
        return $this->resolveRelationProperty($this->findRelationMethod($classReflection, $propertyName), $classReflection);
    }

    private function findRelationMethod(ClassReflection $classReflection, string $methodName): ?MethodCall
    {
        foreach ($this->extendersResolver->getExtenders() as $extender) {
            if (! $extender->isExtender('Model')) {
                continue;
            }

            foreach (array_merge([$classReflection->getName()], $classReflection->getParentClassesNames()) as $className) {
                if ($className === 'Flarum\Database\AbstractModel') {
                    break;
                }

                if ($extender->extends($className)) {
                    if ($methodCalls = $extender->findMethodCalls('belongsTo', 'belongsToMany', 'hasMany', 'hasOne')) {
                        foreach ($methodCalls as $methodCall) {
                            if ($methodCall->arguments[0]->value === $methodName) {
                                return $methodCall;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    private function resolveRelationMethod(MethodCall $methodCall, ClassReflection $classReflection): MethodReflection
    {
        return new RelationMethod($methodCall, $classReflection);
    }

    private function resolveRelationProperty(MethodCall $methodCall, ClassReflection $classReflection): PropertyReflection
    {
        return new RelationProperty($methodCall, $classReflection);
    }
}
