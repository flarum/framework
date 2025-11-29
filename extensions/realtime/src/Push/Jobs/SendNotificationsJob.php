<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Illuminate\Contracts\Queue\Queue;

class SendNotificationsJob extends Job
{
    public static ?string $onQueue = null;

    public function __construct(private BlueprintInterface $blueprint, private array $recipients)
    {
        parent::__construct();
    }

    public function handle(Queue $queue): void
    {
        // Only dispatch notification jobs for users on the socket.
        $intersect = $this->connectedUsers()->intersect($this->recipients);

        foreach ($intersect as $user) {
            if ($user->shouldAlert($this->blueprint::getType())) {
                $queue->push(
                    new SendGeneratedPayloadJob('notification', $user, $user, [])
                );
            }
        }
    }
}
