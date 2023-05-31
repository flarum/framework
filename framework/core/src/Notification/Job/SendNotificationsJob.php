<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendNotificationsJob extends AbstractJob
{
    public function __construct(
        private readonly BlueprintInterface $blueprint,
        /** @var User[] */
        private readonly array $recipients = []
    ) {
    }

    public function handle(): void
    {
        Notification::notify($this->recipients, $this->blueprint);
    }
}
