<?php namespace Flarum\Core\Notifications\Commands;

use Flarum\Core\Users\User;

class ReadNotification
{
    /**
     * The ID of the notification to mark as read.
     *
     * @var int
     */
    public $notificationId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $notificationId The ID of the notification to mark as read.
     * @param User $actor The user performing the action.
     */
    public function __construct($notificationId, User $actor)
    {
        $this->notificationId = $notificationId;
        $this->actor = $actor;
    }
}
