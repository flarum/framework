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
use Flarum\Foundation\MaintenanceMode;
use Illuminate\Contracts\Events\Dispatcher;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ExtensionManager::class);
        $this->container->alias(ExtensionManager::class, 'flarum.extensions');

        // Boot extensions when the app is booting. This must be done as a boot
        // listener on the app rather than in the service provider's boot method
        // below, so that extensions have a chance to register things on the
        // container before the core boots up (and starts resolving services).
        $this->container['flarum']->booting(function () {
            /** @var ExtensionManager $manager */
            $manager = $this->container->make('flarum.extensions');
            /** @var MaintenanceMode $maintenance */
            $maintenance = $this->container->make(MaintenanceMode::class);

            if (! $maintenance->isSafeMode()) {
                $manager->extend($this->container);
            }
        });
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(
            Disabling::class,
            DefaultLanguagePackGuard::class
        );
    }
}
