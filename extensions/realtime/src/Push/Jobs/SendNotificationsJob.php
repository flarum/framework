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
use Illuminate\Contracts\Queue\Queue;

class SendNotificationsJob extends Job
{
    public static ?string $onQueue = null;

    /**
     * The notification record is inserted by the alert driver's own queued job
     * (Flarum\Notification\Job\SendNotificationsJob), which this job has no
     * ordering guarantee with on concurrent queue workers. When the record
     * isn't visible yet, re-check a bounded number of times instead of
     * dropping the push.
     */
    protected const MAX_ATTEMPTS = 3;
    protected const RETRY_DELAY_SECONDS = 2;

    public function __construct(
        private BlueprintInterface $blueprint,
        private array $recipients,
        private int $attempt = 1
    ) {
        parent::__construct();
    }

    public function handle(Queue $queue): void
    {
        // Only dispatch notification jobs for users on the socket.
        $intersect = $this->connectedUsers()->intersect($this->recipients);

        $missing = [];

        foreach ($intersect as $user) {
            if (! $user->shouldAlert($this->blueprint::getType())) {
                continue;
            }

            // Resolve the exact record this blueprint produced for the user.
            // A latest()-of-type lookup is not equivalent: `created_at` has
            // second granularity and restored records keep their original
            // timestamp, so with several notifications of the same type it
            // can resolve to a different, older record — pushing a stale
            // notification to the client.
            $notification = Notification::matchingBlueprint($this->blueprint)
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($notification) {
                $queue->push(
                    new SendGeneratedPayloadJob('notification', $notification, $user)
                );
            } elseif ($this->attempt < self::MAX_ATTEMPTS) {
                $missing[] = $user;
            }
        }

        if (count($missing)) {
            $queue->later(
                self::RETRY_DELAY_SECONDS,
                new self($this->blueprint, $missing, $this->attempt + 1)
            );
        }
    }
}
