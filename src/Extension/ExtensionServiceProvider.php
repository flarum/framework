<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extension\Event\Disabling;
use Flarum\Foundation\AbstractServiceProvider;

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
        // container before the core boots up (and starts resolving services).
        $this->app['flarum']->booting(function () {
            $this->app->make('flarum.extensions')->extend($this->app);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->make('events')->listen(
            Disabling::class,
            DefaultLanguagePackGuard::class
        );
    }
}
