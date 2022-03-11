<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Contracts\Types\Pipes;

use Closure;
use Flarum\PHPStan\Contracts\Types\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    /**
     * @param  \Flarum\PHPStan\Contracts\Types\PassableContract  $passable
     * @param  \Closure  $next
     * @return void
     */
    public function handle(PassableContract $passable, Closure $next): void;
}
