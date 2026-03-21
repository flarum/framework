<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Discussion\Discussion;
use Illuminate\Contracts\Queue\Queue;

/**
 * Broadcasts a flag/moderation event to all connected users who have
 * permission to view flags on the given discussion.
 */
class SendFlaggedJob extends Job
{
    public function __construct(
        private Discussion $discussion,
        private string $eventName = 'flagged'
    ) {
        parent::__construct();
    }

    public function handle(Queue $queue): void
    {
        $users = $this->connectedUsers($this->discussion);

        foreach ($users as $user) {
            if ($user->cannot('discussion.viewFlags', $this->discussion)) {
                continue;
            }

            // Update the moderator's flag badge count.
            $queue->push(
                new SendGeneratedPayloadJob($this->eventName, $user, $user)
            );

            // Trigger a discussion stream reload so the post flagged UI appears.
            $queue->push(
                new SendGeneratedPayloadJob($this->eventName.'Stream', $this->discussion, $user)
            );
        }
    }
}
