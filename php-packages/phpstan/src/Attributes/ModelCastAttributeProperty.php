<?php

namespace Flarum\PHPStan\Attributes;

use Flarum\PHPStan\Extender\MethodCall;
use Flarum\PHPStan\Extender\Resolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\NullType;
use PHPStan\Type\UnionType;

class ModelCastAttributeProperty implements PropertiesClassReflectionExtension
{
    /** @var Resolver */
    private $extendersResolver;
    /** @var \PHPStan\PhpDoc\TypeStringResolver */
    private $typeStringResolver;

    public function __construct(Resolver $extendersResolver, \PHPStan\PhpDoc\TypeStringResolver $typeStringResolver)
    {
        $this->extendersResolver = $extendersResolver;
        $this->typeStringResolver = $typeStringResolver;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->findCastAttributeMethod($classReflection, $propertyName) !== null;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->resolveCastAttributeProperty($this->findCastAttributeMethod($classReflection, $propertyName), $classReflection);
    }

    private function findCastAttributeMethod(ClassReflection $classReflection, string $propertyName): ?MethodCall
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
                    if ($methodCalls = $extender->findMethodCalls('castAttribute')) {
                        foreach ($methodCalls as $methodCall) {
                            if ($methodCall->arguments[0]->value === $propertyName) {
                                return $methodCall;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    private function resolveCastAttributeProperty(?MethodCall $methodCall, ClassReflection $classReflection): PropertyReflection
    {
        return new AttributeProperty($classReflection, new UnionType([
            $this->typeStringResolver->resolve($methodCall->arguments[1]->value),
            new NullType(),
        ]));
    }
}
