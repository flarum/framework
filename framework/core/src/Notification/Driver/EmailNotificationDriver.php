<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Driver;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\MailableInterface;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use ReflectionClass;

class EmailNotificationDriver implements NotificationDriverInterface
{
    /**
     * @var Queue
     */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * {@inheritDoc}
     */
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        if ($blueprint instanceof MailableInterface) {
            $this->mailNotifications($blueprint, $users);
        }
    }

    /**
     * Mail a notification to a list of users.
     *
     * @param MailableInterface&BlueprintInterface $blueprint
     * @param User[] $recipients
     */
    protected function mailNotifications(MailableInterface $blueprint, array $recipients)
    {
        foreach ($recipients as $user) {
            if ($user->shouldEmail($blueprint::getType())) {
                $this->queue->push(new SendEmailNotificationJob($blueprint, $user));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        if ((new ReflectionClass($blueprintClass))->implementsInterface(MailableInterface::class)) {
            User::registerPreference(
                User::getNotificationPreferenceKey($blueprintClass::getType(), 'email'),
                'boolval',
                in_array('email', $driversEnabledByDefault)
            );
        }
    }
}
