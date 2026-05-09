<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class SendEmailNotificationJob extends AbstractJob
{
    public function __construct(
        private readonly MailableInterface&BlueprintInterface $blueprint,
        private readonly User $recipient
    ) {
    }

    public function handle(NotificationMailer $mailer, CacheRepository $cache): void
    {
        // Race guard for #4622: NotificationSyncer::sync() queues a
        // SendEmailNotificationJob per recipient on every call. When sync()
        // fires twice in quick succession (e.g. Posted then Revised), two
        // identical jobs land in the queue for the same recipient, and
        // without a guard each runs and sends an email. Take a short-lived
        // atomic lock keyed by blueprint+recipient; the first job to claim
        // it sends the email, the rest no-op. The lock TTL just needs to
        // outlive normal mail-send latency — minutes is plenty.
        $store = $cache->getStore();

        if (! $store instanceof LockProvider) {
            // Cache store doesn't support locks (custom driver). Fall back
            // to non-atomic send — the worst case is a duplicate email,
            // same as before this fix.
            $mailer->send($this->blueprint, $this->recipient);

            return;
        }

        $lockKey = sprintf(
            'flarum.notification.email-sent:%s:%d',
            $this->blueprint::getType(),
            $this->recipient->id
        );

        $lock = $store->lock($lockKey, 600);

        if (! $lock->get()) {
            // Another worker has already claimed responsibility for this
            // (blueprint, recipient) email. Drop silently.
            return;
        }

        $mailer->send($this->blueprint, $this->recipient);
    }
}
