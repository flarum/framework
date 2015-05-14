<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;

interface RetractableSender
{
    public function retract(Notification $notification);
}
