<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind('flarum.extensions', ExtensionManager::class);

        $this->app->booting(function (Container $app) {
            $extenders = $app->make('flarum.extensions')->getActiveExtenders();

            foreach ($extenders as $extender) {
                $extender($app);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $events = $this->app->make('events');

        $events->subscribe(DefaultLanguagePackGuard::class);
    }
}
