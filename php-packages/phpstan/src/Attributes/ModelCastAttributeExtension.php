<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Attributes;

use Carbon\Carbon;
use Flarum\PHPStan\Extender\MethodCall;
use Flarum\PHPStan\Extender\Resolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;

class ModelCastAttributeExtension implements PropertiesClassReflectionExtension
{
    /** @var Resolver */
    private $extendersResolver;
    /** @var TypeStringResolver */
    private $typeStringResolver;

    public function __construct(Resolver $extendersResolver, TypeStringResolver $typeStringResolver)
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
                    if ($methodCalls = $extender->findMethodCalls('cast')) {
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

    private function resolveCastAttributeProperty(MethodCall $methodCall, ClassReflection $classReflection): PropertyReflection
    {
        $typeName = $methodCall->arguments[1]->value;
        $type = $this->typeStringResolver->resolve("$typeName|null");

        if (str_contains($typeName, 'date') || $typeName === 'timestamp') {
            $type = new UnionType([
                new ObjectType(Carbon::class),
                new NullType(),
            ]);
        }

        return new AttributeProperty($classReflection, $type);
    }
}
