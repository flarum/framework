<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Storage;
use Flarum\PHPStan\Reflection\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

class StorageMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== Storage::class) {
            return false;
        }

        if ($this->reflectionProvider->getClass(FilesystemManager::class)->hasMethod($methodName)) {
            return true;
        }

        if ($this->reflectionProvider->getClass(FilesystemAdapter::class)->hasMethod($methodName)) {
            return true;
        }

        return false;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        if ($this->reflectionProvider->getClass(FilesystemManager::class)->hasMethod($methodName)) {
            return new StaticMethodReflection(
                $this->reflectionProvider->getClass(FilesystemManager::class)->getMethod($methodName, new OutOfClassScope())
            );
        }

        return new StaticMethodReflection(
            $this->reflectionProvider->getClass(FilesystemAdapter::class)->getMethod($methodName, new OutOfClassScope())
        );
    }
}
