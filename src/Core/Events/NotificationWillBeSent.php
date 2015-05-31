<?php namespace Flarum\Core\Events;

use Flarum\Core\Notifications\NotificationInterface;

class NotificationWillBeSent
{
    public $notification;

    public $users;

    public function __construct(NotificationInterface $notification, array &$users)
    {
        $this->notification = $notification;
        $this->users = $users;
    }
}
