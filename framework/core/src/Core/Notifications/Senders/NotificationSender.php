<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;

interface NotificationSender
{
    public function send(Notification $notification);

    public static function compatibleWith($class);
}
