<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Discussion\Event\Saving;

class SaveSubscriptionToDatabase
{
    public function handle(Saving $event): void
    {
        $discussion = $event->discussion;
        // A request may update only relationships (moving a discussion to
        // another tag, for example), in which case there are no attributes.
        $attributes = $event->data['attributes'] ?? [];

        if (array_key_exists('subscription', $attributes)) {
            $actor = $event->actor;
            $subscription = $attributes['subscription'];

            $actor->assertRegistered();

            $state = $discussion->stateFor($actor);

            if (! in_array($subscription, ['follow', 'ignore'])) {
                $subscription = null;
            }

            $state->subscription = $subscription;
            $state->save();
        }
    }
}
