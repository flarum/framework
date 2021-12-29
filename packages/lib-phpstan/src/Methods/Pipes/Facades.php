<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods\Pipes;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Flarum\PHPStan\Contracts\Methods\PassableContract;
use Flarum\PHPStan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Facades implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if ($concrete = $facadeClass::getFacadeRoot()) {
                $class = get_class($concrete);

                if ($class) {
                    $found = $passable->sendToPipeline($class, true);
                }
            }

            if (! $found && Str::startsWith($passable->getMethodName(), 'assert')) {
                $fakeFacadeClass = $this->getFake($facadeClass);

                if ($passable->getReflectionProvider()->hasClass($fakeFacadeClass)) {
                    assert(class_exists($fakeFacadeClass));
                    $found = $passable->sendToPipeline($fakeFacadeClass, true);
                }
            }
        }

        if (! $found) {
            $next($passable);
        }
    }

    private function getFake(string $facade): string
    {
        $shortClassName = substr($facade, strrpos($facade, '\\') + 1);

        return sprintf('\\Illuminate\\Support\\Testing\\Fakes\\%sFake', $shortClassName);
    }
}
