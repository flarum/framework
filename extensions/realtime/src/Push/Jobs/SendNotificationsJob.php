<?php

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;

class SendNotificationsJob extends Job
{
    public static ?string $onQueue = null;

    public function __construct(private BlueprintInterface $blueprint, private array $recipients)
    {
        parent::__construct();
    }

    public function handle(Queue $queue)
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
