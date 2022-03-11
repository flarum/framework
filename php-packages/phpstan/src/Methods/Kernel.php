<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods;

use Illuminate\Pipeline\Pipeline;
use Flarum\PHPStan\Concerns;
use Flarum\PHPStan\Contracts\Methods\PassableContract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @internal
 */
final class Kernel
{
    use Concerns\HasContainer;

    /**
     * @var PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * Kernel constructor.
     *
     * @param  PhpMethodReflectionFactory  $methodReflectionFactory
     */
    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        ReflectionProvider $reflectionProvider
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @param  ClassReflection  $classReflection
     * @param  string  $methodName
     * @return PassableContract
     */
    public function handle(ClassReflection $classReflection, string $methodName): PassableContract
    {
        $pipeline = new Pipeline($this->getContainer());

        $passable = new Passable($this->methodReflectionFactory, $this->reflectionProvider, $pipeline, $classReflection, $methodName);

        $pipeline->send($passable)
            ->through(
                [
                    Pipes\SelfClass::class,
                    Pipes\Macros::class,
                    Pipes\Contracts::class,
                    Pipes\Facades::class,
                    Pipes\Managers::class,
                    Pipes\Auths::class,
                ]
            )
            ->then(
                function ($method) {
                }
            );

        return $passable;
    }
}
