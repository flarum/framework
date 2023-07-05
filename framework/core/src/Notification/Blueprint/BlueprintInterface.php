<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Blueprint;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * A notification BlueprintInterface, when instantiated, represents a notification about
 * something. The blueprint is used by the NotificationSyncer to commit the
 * notification to the database.
 */
interface BlueprintInterface
{
    /**
     * Get the user that sent the notification.
     */
    public function getFromUser(): ?User;

    /**
     * Get the model that is the subject of this activity.
     */
    public function getSubject(): ?AbstractModel;

    /**
     * Get the data to be stored in the notification.
     */
    public function getData(): mixed;

    /**
     * Get the serialized type of this activity.
     */
    public static function getType(): string;

    /**
     * Get the name of the model class for the subject of this activity.
     *
     * @return class-string<AbstractModel>
     */
    public static function getSubjectModel(): string;
}
