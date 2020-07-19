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
    public function handle(Saving $event)
    {
        $discussion = $event->discussion;
        $data = $event->data;

        if (isset($data['attributes']['subscription'])) {
            $actor = $event->actor;
            $subscription = $data['attributes']['subscription'];

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
