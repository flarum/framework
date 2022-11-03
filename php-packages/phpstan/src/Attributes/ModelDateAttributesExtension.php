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
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;

class ModelDateAttributesExtension implements PropertiesClassReflectionExtension
{
    /** @var Resolver */
    private $extendersResolver;

    public function __construct(Resolver $extendersResolver)
    {
        $this->extendersResolver = $extendersResolver;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->findDateAttributeMethod($classReflection, $propertyName) !== null;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->resolveDateAttributeProperty($this->findDateAttributeMethod($classReflection, $propertyName), $classReflection);
    }

    private function findDateAttributeMethod(ClassReflection $classReflection, string $propertyName): ?MethodCall
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
                    if ($methodCalls = $extender->findMethodCalls('dateAttribute')) {
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

    private function resolveDateAttributeProperty(MethodCall $methodCall, ClassReflection $classReflection): PropertyReflection
    {
        return new AttributeProperty($classReflection, new UnionType([
            new ObjectType(Carbon::class),
            new NullType(),
        ]));
    }
}
