<?php namespace Flarum\Core\Notifications\Events;

use Flarum\Core\Notifications\Blueprint;

class NotificationWillBeSent
{
    /**
     * The blueprint for the notification.
     *
     * @var Blueprint
     */
    public $blueprint;

    /**
     * The users that the notification will be sent to.
     *
     * @var array
     */
    public $users;

    /**
     * @param Blueprint $blueprint
     * @param \Flarum\Core\Users\User[] $users
     */
    public function __construct(Blueprint $blueprint, array &$users)
    {
        $this->blueprint = $blueprint;
        $this->users = $users;
    }
}
