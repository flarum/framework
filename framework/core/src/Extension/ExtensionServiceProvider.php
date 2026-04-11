<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extension\Console\SyncAbandonedExtensionsCommand;
use Flarum\Extension\Console\WeeklySchedule;
use Flarum\Extension\Event\Disabling;
use Flarum\Foundation\AbstractServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton(ExtensionManager::class);
        $this->container->alias(ExtensionManager::class, 'flarum.extensions');

        $this->container->singleton(AbandonedExtensionsFetcher::class, function ($container) {
            return new AbandonedExtensionsFetcher(
                $container->make(ExtensionManager::class),
                $container->make('flarum.settings'),
                new Client(),
                $container->make(Queue::class),
                $container->make(TranslatorInterface::class)
            );
        });

        // Boot extensions when the app is booting. This must be done as a boot
        // listener on the app rather than in the service provider's boot method
        // below, so that extensions have a chance to register things on the
        // container before the core boots up (and starts resolving services).
        $this->container['flarum']->booting(function () {
            $this->container->make('flarum.extensions')->extend($this->container);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(
            Disabling::class,
            DefaultLanguagePackGuard::class
        );

        $this->container->extend('flarum.console.commands', function (array $commands) {
            $commands[] = SyncAbandonedExtensionsCommand::class;

            return $commands;
        });

        $this->container->extend('flarum.console.scheduled', function (array $scheduled) {
            $scheduled[] = [
                'command' => SyncAbandonedExtensionsCommand::class,
                'args' => ['--notify'],
                'callback' => new WeeklySchedule(),
            ];

            return $scheduled;
        });
    }
}
