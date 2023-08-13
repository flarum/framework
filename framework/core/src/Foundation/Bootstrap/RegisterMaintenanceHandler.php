<?php

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
