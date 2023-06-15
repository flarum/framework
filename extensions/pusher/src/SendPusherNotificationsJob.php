<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Pusher;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Pusher\Pusher;

class SendPusherNotificationsJob extends AbstractJob
{
    public function __construct(
        private readonly BlueprintInterface $blueprint,
        /** @var User[] */
        private readonly array $recipients
    ) {
    }

    public function handle(Pusher $pusher): void
    {
        foreach ($this->recipients as $user) {
            if ($user->shouldAlert($this->blueprint::getType())) {
                $pusher->trigger('private-user'.$user->id, 'notification', null);
            }
        }
    }
}
