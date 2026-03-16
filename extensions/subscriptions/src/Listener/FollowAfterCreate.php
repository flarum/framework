<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Discussion\Event\Started;

class FollowAfterCreate
{
    public function handle(Started $event): void
    {
        $actor = $event->actor;

        if ($actor && $actor->exists && $actor->getPreference('followAfterCreate')) {
            $actor->assertRegistered();

            $state = $event->discussion->stateFor($actor);

            $state->subscription = 'follow';
            $state->save();
        }
    }
}
