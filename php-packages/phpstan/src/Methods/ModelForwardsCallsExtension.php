<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Flarum\PHPStan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var BuilderHelper */
    private $builderHelper;

    /** @var ReflectionProvider */
    private $reflectionProvider;

    /** @var EloquentBuilderForwardsCallsExtension */
    private $eloquentBuilderForwardsCallsExtension;

    /** @var array<string, MethodReflection> */
    private $cache = [];

    public function __construct(BuilderHelper $builderHelper, ReflectionProvider $reflectionProvider, EloquentBuilderForwardsCallsExtension $eloquentBuilderForwardsCallsExtension)
    {
        $this->builderHelper = $builderHelper;
        $this->reflectionProvider = $reflectionProvider;
        $this->eloquentBuilderForwardsCallsExtension = $eloquentBuilderForwardsCallsExtension;
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey().'-'.$methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null) {
            $this->cache[$classReflection->getCacheKey().'-'.$methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    /**
     * @param  ClassReflection  $classReflection
     * @param  string  $methodName
     * @return MethodReflection
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getCacheKey().'-'.$methodName];
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        if ($classReflection->getName() !== Model::class && ! $classReflection->isSubclassOf(Model::class)) {
            return null;
        }

        $builderName = $this->builderHelper->determineBuilderName($classReflection->getName());

        if (in_array($methodName, ['increment', 'decrement'], true)) {
            $methodReflection = $classReflection->getNativeMethod($methodName);

            return new class($classReflection, $methodName, $methodReflection) implements MethodReflection
            {
                /** @var ClassReflection */
                private $classReflection;

                /** @var string */
                private $methodName;

                /** @var MethodReflection */
                private $methodReflection;

                public function __construct(ClassReflection $classReflection, string $methodName, MethodReflection $methodReflection)
                {
                    $this->classReflection = $classReflection;
                    $this->methodName = $methodName;
                    $this->methodReflection = $methodReflection;
                }

                public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
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

                public function getPrototype(): \PHPStan\Reflection\ClassMemberReflection
                {
                    return $this;
                }

                public function getVariants(): array
                {
                    return $this->methodReflection->getVariants();
                }

                public function isDeprecated(): \PHPStan\TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function getDeprecatedDescription(): ?string
                {
                    return null;
                }

                public function isFinal(): \PHPStan\TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function isInternal(): \PHPStan\TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function getThrowType(): ?\PHPStan\Type\Type
                {
                    return null;
                }

                public function hasSideEffects(): \PHPStan\TrinaryLogic
                {
                    return TrinaryLogic::createYes();
                }
            };
        }

        $builderReflection = $this->reflectionProvider->getClass($builderName)->withTypes([new ObjectType($classReflection->getName())]);
        $genericBuilderAndModelType = new GenericObjectType($builderName, [new ObjectType($classReflection->getName())]);

        if ($builderReflection->hasNativeMethod($methodName)) {
            $reflection = $builderReflection->getNativeMethod($methodName);

            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($reflection->getVariants());

            $returnType = TypeTraverser::map($parametersAcceptor->getReturnType(), static function (Type $type, callable $traverse) use ($genericBuilderAndModelType) {
                if ($type instanceof TypeWithClassName && $type->getClassName() === Builder::class) {
                    return $genericBuilderAndModelType;
                }

                return $traverse($type);
            });

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                $reflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        if ($this->eloquentBuilderForwardsCallsExtension->hasMethod($builderReflection, $methodName)) {
            return $this->eloquentBuilderForwardsCallsExtension->getMethod($builderReflection, $methodName);
        }

        return null;
    }
}
