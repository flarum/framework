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
    public function register(): void
    {
        // Don't run the logger middleware in API client subrequests. The actor/client/IP are
        // tracked as global state set once by the outer (forum/admin/api) request, so re-running
        // SetLoggerActor on a subrequest would only risk clobbering that state with subrequest
        // values (and parentless subrequests — console, queue — have no IP/session/actor to set
        // at all). Note: flarum/framework#2985 (IP missing on API-client subrequests) is now
        // fixed and parented subrequests do forward the IP, but the exclusion is still the
        // correct design for the global-state reason above.
        $this->container->extend('flarum.api_client.exclude_middleware', function (array $middlewares): array {
            $middlewares[] = SetLoggerActor::class;

            return $middlewares;
        });
    }
}
