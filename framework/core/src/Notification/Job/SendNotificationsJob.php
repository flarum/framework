<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendNotificationsJob extends AbstractJob
{
    /**
     * Notification::notify() uses a raw bulk insert (not upsert), so retrying
     * would create duplicate notification rows in recipients' feeds.
     */
    public int $tries = 1;

    public function __construct(
        private readonly BlueprintInterface&AlertableInterface $blueprint,
        /** @var User[] */
        private readonly array $recipients = []
    ) {
    }

    public function handle(): void
    {
        Notification::notify($this->recipients, $this->blueprint);
    }
}
