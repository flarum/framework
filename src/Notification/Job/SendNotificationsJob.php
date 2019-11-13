<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Carbon\Carbon;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Event\Notifying;
use Flarum\Notification\Event\Sending;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationMailer;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendNotificationsJob extends AbstractJob
{
    /**
     * @var BlueprintInterface
     */
    private $blueprint;

    /**
     * @var array
     */
    private $recipientIds;

    public function __construct(BlueprintInterface $blueprint, array $recipientIds = [])
    {
        $this->blueprint = $blueprint;
        $this->recipientIds = $recipientIds;
    }

    public function handle(NotificationMailer $mailer)
    {
        $now = Carbon::now('utc')->toDateTimeString();
        $recipients = $this->recipientIds;

        event(new Sending($this->blueprint, $recipients));

        $attributes = $this->blueprint->getAttributes();

        Notification::insert(
            array_map(function (User $user) use ($attributes, $now) {
                return $attributes + [
                    'user_id' => $user->id,
                    'created_at' => $now
                ];
            }, $recipients)
        );

        event(new Notifying($this->blueprint, $recipients));

        if ($this->blueprint instanceof MailableInterface) {
            $this->email($mailer, $this->blueprint, $recipients);
        }
    }

    protected function email(NotificationMailer $mailer, MailableInterface $blueprint, array $recipients)
    {
        foreach ($recipients as $user) {
            if ($user->shouldEmail($blueprint::getType())) {
                $mailer->send($blueprint, $user);
            }
        }
    }
}
