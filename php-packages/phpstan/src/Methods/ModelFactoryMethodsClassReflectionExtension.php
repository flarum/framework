<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Methods;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ModelFactoryMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        // Class only available on Laravel 8
        if (! class_exists('\Illuminate\Database\Eloquent\Factories\Factory')) {
            return false;
        }

        if (! $classReflection->isSubclassOf(Factory::class)) {
            return false;
        }

        if (! Str::startsWith($methodName, ['for', 'has'])) {
            return false;
        }

        $relationship = Str::camel(Str::substr($methodName, 3));

        $parent = $classReflection->getParentClass();

        if ($parent === null) {
            return false;
        }

        $modelType = $parent->getActiveTemplateTypeMap()->getType('TModel');

        if ($modelType === null) {
            return false;
        }

        return $modelType->hasMethod($relationship)->yes();
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        return new class($classReflection, $methodName) implements MethodReflection {
            /** @var ClassReflection */
            private $classReflection;

            /** @var string */
            private $methodName;

            public function __construct(ClassReflection $classReflection, string $methodName)
            {
                $this->classReflection = $classReflection;
                $this->methodName = $methodName;
            }

            public function getDeclaringClass(): ClassReflection
            {
                return $this->classReflection;
            }

            public function isStatic(): bool
            {
                return false;
            }

            public function isPrivate(): bool
            {
                return false;
            }

            public function isPublic(): bool
            {
                return true;
            }

            public function getDocComment(): ?string
            {
                return null;
            }

            public function getName(): string
            {
                return $this->methodName;
            }

            public function getPrototype(): ClassMemberReflection
            {
                return $this;
            }

            public function getVariants(): array
            {
                $returnType = new ObjectType($this->classReflection->getName());
                $stateParameter = ParametersAcceptorSelector::selectSingle($this->classReflection->getMethod('state', new OutOfClassScope())->getVariants())->getParameters()[0];
                $countParameter = ParametersAcceptorSelector::selectSingle($this->classReflection->getMethod('count', new OutOfClassScope())->getVariants())->getParameters()[0];

                $variants = [
                    new FunctionVariant(TemplateTypeMap::createEmpty(), null, [], false, $returnType),
                ];

                if (Str::startsWith($this->methodName, 'for')) {
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$stateParameter], false, $returnType);
                } else {
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$countParameter], false, $returnType);
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$stateParameter], false, $returnType);
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$countParameter, $stateParameter], false, $returnType);
                }

                return $variants;
            }

            public function isDeprecated(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): ?string
            {
                return null;
            }

            public function isFinal(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function isInternal(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getThrowType(): ?Type
            {
                return null;
            }

            public function hasSideEffects(): TrinaryLogic
            {
                return TrinaryLogic::createMaybe();
            }
        };
    }
}
