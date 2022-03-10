<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Methods\Pipes;

use Closure;
use Flarum\PHPStan\Contracts\Methods\PassableContract;
use Flarum\PHPStan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class SelfClass implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $className = $passable->getClassReflection()
            ->getName();

        if (! $passable->searchOn($className)) {
            $next($passable);
        }
    }
}
