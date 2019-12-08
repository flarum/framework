<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Session\FileSessionHandler;
use SessionHandlerInterface;

class SessionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('session.handler', function ($app) {
            return new FileSessionHandler(
                $app['files'],
                $app['config']['session.files'],
                $app['config']['session.lifetime']
            );
        });

        $this->app->alias('session.handler', SessionHandlerInterface::class);
    }
}
