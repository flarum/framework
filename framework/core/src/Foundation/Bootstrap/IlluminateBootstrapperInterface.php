<?php

namespace Flarum\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

interface IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void;
}
