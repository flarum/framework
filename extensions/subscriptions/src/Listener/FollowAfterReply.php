<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Post\Event\Posted;

class FollowAfterReply
{
    public function handle(Posted $event)
    {
        $actor = $event->actor;

        if ($actor && $actor->exists && $actor->getPreference('followAfterReply')) {
            $actor->assertRegistered();

            $state = $event->post->discussion->stateFor($actor);

            $state->subscription = 'follow';
            $state->save();
        }
    }
}
