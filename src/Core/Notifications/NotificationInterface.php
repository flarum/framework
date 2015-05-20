<?php namespace Flarum\Core\Notifications;

interface NotificationInterface
{
    /**
     * Get the model that is the subject of this activity.
     *
     * @return \Flarum\Core\Models\Model
     */
    public function getSubject();

    /**
     * Get the user that sent the notification.
     *
     * @return \Flarum\Core\Models\User|null
     */
    public function getSender();

    /**
     * Get the data to be stored in the notification.
     *
     * @return array
     */
    public function getData();

    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string
     */
    public function getEmailView();

    /**
     * Get the subject line for a notification email.
     *
     * @return string
     */
    public function getEmailSubject();

    /**
     * Get the serialized type of this activity.
     *
     * @return string
     */
    public static function getType();

    /**
     * Get the name of the model class for the subject of this activity.
     *
     * @return string
     */
    public static function getSubjectModel();

    /**
     * Whether or not the notification is able to be sent as an email.
     *
     * @return boolean
     */
    public static function isEmailable();
}
