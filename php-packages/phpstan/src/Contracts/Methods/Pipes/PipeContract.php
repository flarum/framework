<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

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
