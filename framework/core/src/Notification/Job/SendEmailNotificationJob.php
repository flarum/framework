<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendEmailNotificationJob extends AbstractJob
{
    public function __construct(
        private readonly MailableInterface $blueprint,
        private readonly User $recipient
    ) {
    }

    public function handle(NotificationMailer $mailer): void
    {
        $mailer->send($this->blueprint, $this->recipient);
    }
}
