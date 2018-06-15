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
        $this->app->singleton(ExtensionManager::class);
        $this->app->alias(ExtensionManager::class, 'flarum.extensions');

        // Boot extensions when the app is booting. This must be done as a boot
        // listener on the app rather than in the service provider's boot method
        // below, so that extensions have a chance to register things on the
        // container before the core boot code runs.
        $this->app->booting(function (Container $app) {
            $app->make('flarum.extensions')->extend($app);
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
