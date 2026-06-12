<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\User\User;
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
            if (! $user->shouldAlert($this->blueprint::getType())) {
                continue;
            }

            $notification = $this->notificationFor($user);

            if ($notification) {
                $queue->push(
                    new SendGeneratedPayloadJob('notification', $notification, $user)
                );
            }
        }
    }

    /**
     * Find the stored notification that the fired blueprint produced for this recipient.
     *
     * We match on the blueprint itself (type, subject, from-user and data) rather than just the
     * type, so the broadcast carries the notification the event actually created — not whichever
     * notification of the same type happens to be newest, which would surface a previous,
     * unrelated notification (e.g. an older mention from a different user) in the toast.
     */
    public function notificationFor(User $user): ?Notification
    {
        return Notification::matchingBlueprint($this->blueprint)
            ->where('user_id', $user->id)
            ->first();
    }
}
