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

use Flarum\Discussion\Event\Saving;
use Flarum\User\AssertPermissionTrait;

class SaveSubscriptionToDatabase
{
    use AssertPermissionTrait;

    public function handle(Saving $event)
    {
        $discussion = $event->discussion;
        $data = $event->data;

        if (isset($data['attributes']['subscription'])) {
            $actor = $event->actor;
            $subscription = $data['attributes']['subscription'];

            $this->assertRegistered($actor);

            $state = $discussion->stateFor($actor);

            if (! in_array($subscription, ['follow', 'ignore'])) {
                $subscription = null;
            }

            $state->subscription = $subscription;
            $state->save();
        }
    }
}
