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
        $type = $this->blueprint::getType();

        // Narrow to recipients who are on the socket and want an alert for this type. The
        // shouldAlert() check reads the user's preferences (already loaded on the model), so
        // this filtering costs no queries.
        $recipients = $this->connectedUsers()
            ->intersect($this->recipients)
            ->filter(fn (User $user) => $user->shouldAlert($type));

        if ($recipients->isEmpty()) {
            return;
        }

        // One query for the whole set, rather than one per recipient. We match on the blueprint
        // (type, subject, from-user and data) rather than just the type, so the broadcast carries
        // the notification this event actually created — not whichever notification of the same
        // type happens to be newest, which would surface a previous, unrelated notification (e.g.
        // an older mention from a different user) in the toast.
        //
        // This job is dispatched from the Notification\Event\Sent listener, i.e. after the records
        // have been inserted, so the matching rows are guaranteed to exist here.
        $notifications = Notification::matchingBlueprint($this->blueprint)
            ->whereIn('user_id', $recipients->map(fn (User $user) => $user->id)->all())
            ->get()
            ->keyBy('user_id');

        foreach ($recipients as $user) {
            if ($notification = $notifications->get($user->id)) {
                $queue->push(
                    new SendGeneratedPayloadJob('notification', $notification, $user)
                );
            }
        }
    }
}
