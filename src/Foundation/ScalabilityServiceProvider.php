<?php

namespace Flarum\Foundation;

use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\SyncQueue;

class ScalabilityServiceProvider extends AbstractServiceProvider
{
    public function boot(Dispatcher $events, Queue $queue)
    {
        if ($queue instanceof SyncQueue) {
            $events->listen(JobProcessing::class, [$this, 'trackQueueLoad']);
        }

        $events->listen(Deserializing::class, [$this, 'recommendations']);
    }

    public function trackQueueLoad(JobProcessing $event)
    {
        /** @var Repository $cache */
        $cache = resolve('cache.store');

        // Retrieve existing queue load.
        $count = (int) $cache->get('flarum.scalability.queue-load', 0);

        $count++;

        // Store the queue load, but only for one minute.
        $cache->set('flarum.scalability.queue-load', $count, 60);

        // If within that minute 10 queue tasks were fired, we need to suggest an alternative driver.
        if ($count > 10) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);
            $settings->set('flarum.scalability.queue-recommended', true);
        }
    }

    public function recommendations(Deserializing $event)
    {
        /** @var Config $config */
        $config = resolve(Config::class);

        // Toggles the advanced pane for admins.
        $event->settings['advanced_settings_pane_enabled'] = $event->settings['flarum.scalability.queue-recommended']
            ?? $config->offsetGet('advanced-settings')
            ?? false;
    }
}
