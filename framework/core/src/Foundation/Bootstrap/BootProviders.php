<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Bootstrap;

use Flarum\Foundation\SafeBooter;
use Illuminate\Contracts\Foundation\Application;

class BootProviders implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        (new SafeBooter($app))->boot();
    }
}
