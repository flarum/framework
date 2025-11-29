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

class SendFlaggedJob extends Job
{
    public function __construct(private Discussion $discussion)
    {
        parent::__construct();
    }

    public function handle(Queue $queue): void
    {
        $users = $this->connectedUsers($this->discussion);

        foreach ($users as $user) {
            if ($user->cannot('discussion.viewFlags', $this->discussion)) {
                continue;
            }

            $queue->push(
                new SendGeneratedPayloadJob('flagged', $user, $user)
            );
        }
    }
}
