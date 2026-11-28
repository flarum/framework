<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Discussion\Event\UserRead;
use Flarum\Notification\Event\DeletedAll;
use Flarum\Notification\Event\Read;
use Flarum\Notification\Event\ReadAll;
use Flarum\Realtime\Push\Jobs\SendOwnStateJob;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

class OwnStateSubscriber extends Subscriber
{
    public const NOTIFICATIONS = 'ownNotifications';
    public const DISCUSSION_READ = 'ownDiscussionRead';
    public const ALL_DISCUSSIONS_READ = 'ownAllDiscussionsRead';

    public function subscribe(Dispatcher $events): void
    {
        $this->listen(Read::class, function (Read $event) {
            $this->push($event->actor->id, self::NOTIFICATIONS, ['read' => [
                'id' => (string) $event->notification->id,
                'type' => $event->notification->type,
                'subjectId' => (string) $event->notification->subject_id,
            ]], true);
        });

        $this->listen(ReadAll::class, function (ReadAll $event) {
            // Only what existed then: a tab may have received newer ones since.
            $this->push($event->actor->id, self::NOTIFICATIONS, ['allRead' => $event->timestamp->format(DATE_ATOM)], true);
        });

        $this->listen(DeletedAll::class, function (DeletedAll $event) {
            $this->push($event->user->id, self::NOTIFICATIONS, ['allDeleted' => $event->timestamp->format(DATE_ATOM)], true);
        });

        $this->listen(UserRead::class, function (UserRead $event) {
            $state = $event->state;

            $this->push((int) $state->user_id, self::DISCUSSION_READ, [
                'id' => (string) $state->discussion_id,
                'lastReadPostNumber' => (int) $state->last_read_post_number,
                'lastReadAt' => $state->last_read_at?->toAtomString(),
            ]);
        });

        // Neither of these raises an event of its own.
        $this->listen('eloquent.saved: '.User::class, function (User $user) {
            if ($user->wasChanged('read_notifications_at')) {
                // Opening the list makes every notification seen, so none are new.
                $this->push($user->id, self::NOTIFICATIONS, ['newNotificationCount' => 0]);
            }

            if ($user->wasChanged('marked_all_as_read_at') && $user->marked_all_as_read_at) {
                $this->push($user->id, self::ALL_DISCUSSIONS_READ, [
                    'markedAllAsReadAt' => $user->marked_all_as_read_at->toAtomString(),
                ]);
            }
        });
    }

    private function push(int $userId, string $event, array $data, bool $withNotificationCounts = false): void
    {
        // Raised by requests that read or tidy up: a queue that is down must not fail them.
        try {
            $this->queue()->push(new SendOwnStateJob($userId, $event, $data, $withNotificationCounts));
        } catch (Throwable $e) {
            resolve(LoggerInterface::class)->warning('[realtime] Could not queue own state: '.$e->getMessage());
        }
    }
}
