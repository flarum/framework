<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Realtime\Push\Jobs\SendDialogMessageJob;
use Flarum\Realtime\Push\Jobs\SendFlaggedJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Single event subscriber that wires up all realtime broadcast listeners.
 *
 * Core Flarum events (discussion started/replied/revised/renamed) are always
 * registered here. Third-party and bundled extension events are registered
 * via the Realtime extender and stored in RealtimeRegistry.
 */
class EventSubscriber extends Subscriber
{
    public function __construct(private RealtimeRegistry $registry)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        // Core events — always registered regardless of other extensions.
        $this->listen(Started::class, [$this, 'started']);
        $this->listen(Posted::class, [$this, 'replied']);
        $this->listen(Revised::class, [$this, 'revised']);
        $this->listen(Renamed::class, [$this, 'renamed']);

        // Extension-registered model events.
        foreach ($this->registry->getModelEvents() as $entry) {
            $this->listen($entry['events'], function (object $event) use ($entry) {
                $model = ($entry['getModel'])($event);
                $actor = $entry['getActor'] ? ($entry['getActor'])($event) : null;
                $name = $entry['eventName'] ?? get_class($event);

                $this->queue()->push(new SendTriggerJob($name, $model, $actor));
            });
        }

        // Extension-registered dialog message events.
        foreach ($this->registry->getDialogEvents() as $entry) {
            $this->listen($entry['events'], function (object $event) use ($entry) {
                $message = ($entry['getMessage'])($event);

                $this->queue()->push(new SendDialogMessageJob(get_class($event), $message));
            });
        }

        // Extension-registered flag/moderation events.
        foreach ($this->registry->getFlagEvents() as $entry) {
            $this->listen($entry['events'], function (object $event) use ($entry) {
                $discussion = ($entry['getDiscussion'])($event);

                $this->queue()->push(new SendFlaggedJob($discussion, $entry['eventName']));
            });
        }
    }

    public function started(Started $event): void
    {
        $this->queue()->push(new SendTriggerJob(
            get_class($event),
            $event->discussion,
            $event->actor
        ));
    }

    public function replied(Posted $event): void
    {
        // Skip the first post — the Started event covers discussion creation.
        if ($event->post->number === 1) {
            return;
        }

        $this->queue()->push(new SendTriggerJob(
            get_class($event),
            $event->post,
            $event->actor
        ));
    }

    public function revised(Revised $event): void
    {
        $this->queue()->push(new SendTriggerJob(
            'revisedEvent',
            $event->post,
            $event->actor
        ));
    }

    public function renamed(Renamed $event): void
    {
        $this->queue()->push(new SendTriggerJob(
            'discussionRenamed',
            $event->discussion,
            $event->actor
        ));
    }
}
