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
use Pusher;

class SendPusherNotificationsJob extends AbstractJob
{
    /**
     * @var BlueprintInterface
     */
    private $blueprint;

    /**
     * @var User[]
     */
    private $recipients;

    public function __construct(BlueprintInterface $blueprint, array $recipients)
    {
        parent::__construct();

        $this->blueprint = $blueprint;
        $this->recipients = $recipients;
    }

    public function handle(Pusher $pusher)
    {
        foreach ($this->recipients as $user) {
            if ($user->shouldAlert($this->blueprint::getType())) {
                $pusher->trigger('private-user'.$user->id, 'notification', null);
            }
        }
    }
}
