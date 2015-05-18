<?php namespace Flarum\Core\Notifications\Types;

abstract class Notification
{
    /**
     * Returns the serialized type of this notification.
     *
     * This method should be overwritten by subclasses.
     *
     * @return string
     */
    public static function getType()
    {
        return 'notification';
    }
}
