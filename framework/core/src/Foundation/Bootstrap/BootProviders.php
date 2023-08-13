<?php

namespace Flarum\Foundation\Bootstrap;

use Flarum\Foundation\Bootstrap\IlluminateBootstrapperInterface;
use Flarum\Foundation\SafeBooter;
use Illuminate\Contracts\Foundation\Application;

class BootProviders implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        (new SafeBooter($app))->boot();
    }
}
