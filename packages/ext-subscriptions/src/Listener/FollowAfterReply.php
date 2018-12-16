<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Post\Event\Posted;
use Flarum\User\AssertPermissionTrait;

class FollowAfterReply
{
    use AssertPermissionTrait;

    public function handle(Posted $event)
    {
        $actor = $event->actor;

        if ($actor && $actor->exists && $actor->getPreference('followAfterReply')) {
            $this->assertRegistered($actor);

            $state = $event->post->discussion->stateFor($actor);

            $state->subscription = 'follow';
            $state->save();
        }
    }
}
