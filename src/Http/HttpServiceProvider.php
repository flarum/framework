<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\AbstractServiceProvider;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.http.csrfExemptPaths', function () {
            return ['/api/token'];
        });

        $this->app->bind(Middleware\CheckCsrfToken::class, function ($app) {
            return new Middleware\CheckCsrfToken($app->make('flarum.http.csrfExemptPaths'));
        });
    }
}
