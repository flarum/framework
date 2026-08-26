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
        parent::__construct();
    }

    public function handle(NotificationMailer $mailer, CacheRepository $cache): void
    {
        // Race guard for #4622: NotificationSyncer::sync() queues a
        // SendEmailNotificationJob per recipient on every call. When sync()
        // fires twice in quick succession (e.g. Posted then Revised), two
        // identical jobs land in the queue for the same recipient, and
        // without a guard each runs and sends an email. Take a short-lived
        // atomic lock keyed by the notification's identity and recipient; the
        // first job to claim it sends the email, the rest no-op. The lock TTL
        // just needs to outlive normal mail-send latency — minutes is plenty.
        $store = $cache->getStore();

        if (! $store instanceof LockProvider) {
            // Cache store doesn't support locks (custom driver). Fall back
            // to non-atomic send — the worst case is a duplicate email,
            // same as before this fix.
            $mailer->send($this->blueprint, $this->recipient);

            return;
        }

        // Key the lock on the notification's identity — its subject and data,
        // not just its type — so that only genuinely duplicate jobs are
        // suppressed. Keying on type alone silently dropped a legitimate second
        // notification of the same type to the same user within the TTL (e.g. a
        // GDPR erasure request made, cancelled, then made again).
        $lockKey = $this->lockKey();

        $lock = $store->lock($lockKey, 600);

        if (! $lock->get()) {
            // Another worker has already claimed responsibility for this
            // (notification, recipient) email. Drop silently.
            return;
        }

        $mailer->send($this->blueprint, $this->recipient);
    }

    /**
     * A lock key identifying this exact notification for this recipient. Two
     * jobs share it only when they represent the same notification — same type,
     * sender, subject and data — so a repeat of the same event is deduplicated
     * while a distinct notification of the same type still sends.
     */
    private function lockKey(): string
    {
        $subject = $this->blueprint->getSubject();
        $data = $this->blueprint->getData();

        $identity = implode(':', [
            $this->blueprint::getType(),
            ($fromUser = $this->blueprint->getFromUser()) ? $fromUser->id : '',
            $subject ? $subject->getKey() : '',
            $data === null ? '' : md5(json_encode($data)),
        ]);

        return sprintf(
            'flarum.notification.email-sent:%s:%d',
            md5($identity),
            $this->recipient->id
        );
    }
}
