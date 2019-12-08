<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Blueprint;

/**
 * A notification BlueprintInterface, when instantiated, represents a notification about
 * something. The blueprint is used by the NotificationSyncer to commit the
 * notification to the database.
 */
interface BlueprintInterface
{
    /**
     * Get the user that sent the notification.
     *
     * @return \Flarum\User\User|null
     */
    public function getFromUser();

    /**
     * Get the model that is the subject of this activity.
     *
     * @return \Flarum\Database\AbstractModel|null
     */
    public function getSubject();

    /**
     * Get the data to be stored in the notification.
     *
     * @return array|null
     */
    public function getData();

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
}
