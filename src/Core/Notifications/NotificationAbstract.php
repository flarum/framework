<?php namespace Flarum\Core\Notifications;

abstract class NotificationAbstract implements NotificationInterface
{
    /**
     * Get the user that sent the notification.
     *
     * @return \Flarum\Core\Models\User|null
     */
    public function getSender()
    {
        return null;
    }

    /**
     * Get the data to be stored in the notification.
     *
     * @return array
     */
    public function getData()
    {
        return null;
    }

    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string
     */
    public function getEmailView()
    {
        return '';
    }

    /**
     * Get the subject line for a notification email.
     *
     * @return string
     */
    public function getEmailSubject()
    {
        return '';
    }

    /**
     * Whether or not the notification is able to be sent as an email.
     *
     * @return boolean
     */
    public static function isEmailable()
    {
        return false;
    }
}
