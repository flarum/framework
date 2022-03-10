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
        $this->container->singleton('session.handler', function ($container) {
            return new FileSessionHandler(
                $container['files'],
                $container['config']['session.files'],
                $container['config']['session.lifetime']
            );
        });

        $this->container->alias('session.handler', SessionHandlerInterface::class);
    }
}
