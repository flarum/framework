<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods\Pipes;

use Closure;
use function get_class;
use Illuminate\Support\Str;
use Flarum\PHPStan\Concerns;
use Flarum\PHPStan\Contracts\Methods\PassableContract;
use Flarum\PHPStan\Contracts\Methods\Pipes\PipeContract;
use PHPStan\Reflection\ClassReflection;

/**
 * @internal
 */
final class Contracts implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $found = false;

        foreach ($this->concretes($passable->getClassReflection()) as $concrete) {
            if ($found = $passable->sendToPipeline($concrete)) {
                break;
            }
        }

        if (! $found) {
            $next($passable);
        }
    }

    /**
     * @param  \PHPStan\Reflection\ClassReflection  $classReflection
     * @return class-string[]
     */
    private function concretes(ClassReflection $classReflection): array
    {
        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $class = get_class($concrete);

                if ($class) {
                    return [$class];
                }
            }
        }

        return [];
    }
}
