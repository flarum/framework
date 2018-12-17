<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listener;

use Flarum\Notification\Event\Sending;
use Pusher;

class PushNotification
{
    /**
     * @var Pusher
     */
    protected $pusher;

    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    public function handle(Sending $event)
    {
        $blueprint = $event->blueprint;

        foreach ($event->users as $user) {
            if ($user->shouldAlert($blueprint::getType())) {
                $this->pusher->trigger('private-user'.$user->id, 'notification', null);
            }
        }
    }
}
