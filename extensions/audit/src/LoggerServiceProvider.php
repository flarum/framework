<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Audit\Middleware\SetLoggerActor;
use Flarum\Foundation\AbstractServiceProvider;

class LoggerServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // We cannot run the logger middleware in API client subrequests because the IP isn't available there
        // https://github.com/flarum/core/issues/2985
        // This isn't an issue since the same middleware already runs on forum middlewares and everything is global
        $this->container->extend('flarum.api_client.exclude_middleware', function (array $middlewares): array {
            $middlewares[] = SetLoggerActor::class;

            return $middlewares;
        });
    }
}
