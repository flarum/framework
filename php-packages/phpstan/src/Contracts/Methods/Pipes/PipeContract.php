<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Contracts\Methods\Pipes;

use Closure;
use Flarum\PHPStan\Contracts\Methods\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    /**
     * @param  \Flarum\PHPStan\Contracts\Methods\PassableContract  $passable
     * @param  \Closure  $next
     * @return void
     */
    public function handle(PassableContract $passable, Closure $next): void;
}
