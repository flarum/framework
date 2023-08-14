<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Bootstrap;

use Flarum\Foundation\MaintenanceModeHandler;
use Illuminate\Contracts\Foundation\Application;

class RegisterMaintenanceHandler implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        $app->instance('flarum.maintenance.handler', new MaintenanceModeHandler);
    }
}
