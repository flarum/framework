<?php namespace Flarum\Core\Notifications\Types;

interface AlertableNotification
{
    /**
     * Get the data to be stored in the alert.
     *
     * @return array
     */
    public function getAlertData();

    /**
     * Get the user that sent the notification.
     *
     * @return \Flarum\Core\Models\User|null
     */
    public function getSender();

    /**
     * Get the model that the notification is about.
     *
     * @return \Flarum\Core\Models\Model
     */
    public function getSubject();

    /**
     * Get the class name of this notification type's subject model.
     *
     * @return string
     */
    public static function getSubjectModel();
}
