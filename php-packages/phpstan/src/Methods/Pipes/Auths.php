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
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use function in_array;

/**
 * @internal
 */
final class Auths implements PipeContract
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    /**
     * @var string[]
     */
    private $classes = [
        Authenticatable::class,
        CanResetPassword::class,
        Authorizable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflectionName = $passable->getClassReflection()
            ->getName();

        $found = false;

        $config = $this->resolve('config');

        if ($config !== null && in_array($classReflectionName, $this->classes, true)) {
            $authModel = $this->getAuthModel($config);

            if ($authModel !== null) {
                $found = $passable->sendToPipeline($authModel);
            }
        } elseif ($classReflectionName === \Illuminate\Contracts\Auth\Factory::class || $classReflectionName === \Illuminate\Auth\AuthManager::class) {
            $found = $passable->sendToPipeline(
                \Illuminate\Contracts\Auth\Guard::class
            );
        }

        if (! $found) {
            $next($passable);
        }
    }
}
