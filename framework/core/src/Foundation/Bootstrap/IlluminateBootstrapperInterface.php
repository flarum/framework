<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

interface IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void;
}
