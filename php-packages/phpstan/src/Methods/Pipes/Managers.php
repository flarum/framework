<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Methods\Pipes;

use Closure;
use Flarum\PHPStan\Concerns;
use Flarum\PHPStan\Contracts\Methods\PassableContract;
use Flarum\PHPStan\Contracts\Methods\Pipes\PipeContract;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * @internal
 */
final class Managers implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Manager::class)) {
            $driver = null;

            $concrete = $this->resolve(
                $classReflection->getName()
            );

            try {
                $driver = $concrete->driver();
            } catch (InvalidArgumentException $exception) {
                // ..
            }

            if ($driver !== null) {
                $class = get_class($driver);

                if ($class) {
                    $found = $passable->sendToPipeline($class);
                }
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}
