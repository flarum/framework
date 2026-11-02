<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Listener;

use Flarum\Ideation\Event\StatusChanged;
use Flarum\Ideation\Job\NotifyVoters;
use Flarum\Ideation\Notification\IdeaShippedBlueprint;
use Flarum\Ideation\Post\IdeationStatusPost;
use Flarum\Ideation\Status;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Contracts\Queue\Queue;

class RecordStatusChange
{
    public function __construct(
        protected Queue $queue,
        protected NotificationSyncer $notifications
    ) {
    }

    public function handle(StatusChanged $event): void
    {
        $discussion = $event->discussion;

        $discussion->mergePost(IdeationStatusPost::reply(
            $discussion->id,
            $event->actor->id ?: null,
            $event->old,
            $event->new,
            $event->mergedInto?->id,
            $event->via,
        ));

        // The voters are told in the background: on a popular idea that is
        // thousands of notifications, and the moderator's click should not
        // wait for them.
        if ($event->new === Status::SHIPPED) {
            // An automatic ship was nobody's click, so nobody is left out.
            $this->queue->push(new NotifyVoters($discussion->id, $event->via === null ? $event->actor->id : 0));
        } elseif ($event->old === Status::SHIPPED) {
            // Moved back out of Shipped (a slip of the mouse, usually): take
            // the alert back. An email already sent cannot be.
            $this->notifications->delete(new IdeaShippedBlueprint($discussion));
        }
    }
}
