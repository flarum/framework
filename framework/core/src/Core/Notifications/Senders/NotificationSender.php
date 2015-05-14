<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Models\User;

interface NotificationSender
{
    public function send(Notification $notification, User $user);

    public static function compatibleWith($class);
}
