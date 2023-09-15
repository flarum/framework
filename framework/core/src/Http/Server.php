<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\SiteInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Symfony\Component\HttpFoundation\Response;

class Server
{
    public function __construct(
        private readonly SiteInterface $site
    ) {
    }

    public function listen(): void
    {
        $siteApp = $this->site->init();
        $app = $siteApp->getContainer();
        $globalMiddleware = $siteApp->getMiddlewareStack();

        $this
            ->handle(Request::capture(), $app, $globalMiddleware)
            ->send();
    }

    public function handle(Request $request, Application $app, array $globalMiddleware): Response
    {
        $app->instance('request', $request);

        $this->bootstrap($app);

        return (new Pipeline($app))
            ->send($request)
            ->through($globalMiddleware)
            ->then(function (Request $request) use ($app) {
                $app->instance('request', $request);

                return $app->make(Router::class)->dispatch($request);
            });
    }

    public function bootstrap(Application $app): void
    {
        if (! $app->hasBeenBootstrapped()) {
            $app->bootstrapWith(
                $this->site->bootstrappers()
            );
        }
    }
}
