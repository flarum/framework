<?php namespace Flarum\Core\Commands;

class ReadNotificationCommand
{
    public $notificationId;

    public $user;

    public function __construct($notificationId, $user)
    {
        $this->notificationId = $notificationId;
        $this->user = $user;
    }
}
