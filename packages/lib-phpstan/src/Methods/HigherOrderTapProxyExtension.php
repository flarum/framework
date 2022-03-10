<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods;

use Illuminate\Support\HigherOrderTapProxy;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ObjectType;

final class HigherOrderTapProxyExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== HigherOrderTapProxy::class) {
            return false;
        }

        $templateTypeMap = $classReflection->getActiveTemplateTypeMap();

        $templateType = $templateTypeMap->getType('TClass');

        if (! $templateType instanceof ObjectType) {
            return false;
        }

        if ($templateType->getClassReflection() === null) {
            return false;
        }

        return $templateType->hasMethod($methodName)->yes();
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        /** @var ObjectType $templateType */
        $templateType = $classReflection->getActiveTemplateTypeMap()->getType('TClass');

        /** @var ClassReflection $reflection */
        $reflection = $templateType->getClassReflection();

        return $reflection->getMethod($methodName, new OutOfClassScope());
    }
}
