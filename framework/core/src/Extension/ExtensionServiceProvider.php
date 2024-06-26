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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
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
        $this->container['flarum']->booting(function (Container $container) {
            /** @var ExtensionManager $manager */
            $manager = $container->make('flarum.extensions');

            $manager->extend($container);
        });
    }

    public function boot(Dispatcher $events, SettingsRepositoryInterface $settings): void
    {
        BisectState::setSettings($settings);

        $events->listen(
            Disabling::class,
            DefaultLanguagePackGuard::class
        );
    }
}
