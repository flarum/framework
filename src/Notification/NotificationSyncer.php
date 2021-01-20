<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\User\User;

/**
 * The Notification Syncer commits notification blueprints to the database, and
 * sends them via email depending on user preference. Where a blueprint
 * represents a single notification, the syncer associates it with a particular
 * user(s) and makes it available in their inbox.
 */
class NotificationSyncer
{
    /**
     * Whether or not notifications are being limited to one per user.
     *
     * @var bool
     */
    protected static $onePerUser = false;

    /**
     * An internal list of user IDs that notifications have been sent to.
     *
     * @var int[]
     */
    protected static $sentTo = [];

    /**
     * A map of notification drivers.
     *
     * @var NotificationDriverInterface[]
     */
    protected static $notificationDrivers = [];

    /**
     * @var array
     */
    protected static $beforeSendingCallbacks = [];

    /**
     * Sync a notification so that it is visible to the specified users, and not
     * visible to anyone else. If it is being made visible for the first time,
     * attempt to send the user an email.
     *
     * @param \Flarum\Notification\Blueprint\BlueprintInterface $blueprint
     * @param User[] $users
     * @return void
     */
    public function sync(Blueprint\BlueprintInterface $blueprint, array $users)
    {
        // Find all existing notification records in the database matching this
        // blueprint. We will begin by assuming that they all need to be
        // deleted in order to match the provided list of users.
        $toDelete = Notification::matchingBlueprint($blueprint)->get();
        $toUndelete = [];
        $newRecipients = [];

        // For each of the provided users, check to see if they already have
        // a notification record in the database. If they do, we will make sure
        // it isn't marked as deleted. If they don't, we will want to create a
        // new record for them.
        foreach ($users as $user) {
            if (! ($user instanceof User)) {
                continue;
            }

            $existing = $toDelete->first(function ($notification) use ($user) {
                return $notification->user_id === $user->id;
            });

            if ($existing) {
                $toUndelete[] = $existing->id;
                $toDelete->forget($toDelete->search($existing));
            } elseif (! static::$onePerUser || ! in_array($user->id, static::$sentTo)) {
                $newRecipients[] = $user;
                static::$sentTo[] = $user->id;
            }
        }

        // Delete all of the remaining notification records which weren't
        // removed from this collection by the above loop. Un-delete the
        // existing records that we want to keep.
        if (count($toDelete)) {
            $this->setDeleted($toDelete->pluck('id')->all(), true);
        }

        if (count($toUndelete)) {
            $this->setDeleted($toUndelete, false);
        }

        foreach (static::$beforeSendingCallbacks as $callback) {
            $newRecipients = $callback($blueprint, $newRecipients);
        }

        // Create a notification record, and send an email, for all users
        // receiving this notification for the first time (we know because they
        // didn't have a record in the database). As both operations can be
        // intensive on resources (database and mail server), we queue them.
        foreach (static::getNotificationDrivers() as $driverName => $driver) {
            $driver->send($blueprint, $newRecipients);
        }
    }

    /**
     * Delete a notification for all users.
     *
     * @param \Flarum\Notification\Blueprint\BlueprintInterface $blueprint
     * @return void
     */
    public function delete(BlueprintInterface $blueprint)
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => true]);
    }

    /**
     * Restore a notification for all users.
     *
     * @param BlueprintInterface $blueprint
     * @return void
     */
    public function restore(BlueprintInterface $blueprint)
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => false]);
    }

    /**
     * Limit notifications to one per user for the entire duration of the given
     * callback.
     *
     * @param callable $callback
     * @return void
     */
    public function onePerUser(callable $callback)
    {
        static::$sentTo = [];
        static::$onePerUser = true;

        $callback();

        static::$onePerUser = false;
    }

    /**
     * Set the deleted status of a list of notification records.
     *
     * @param int[] $ids
     * @param bool $isDeleted
     */
    protected function setDeleted(array $ids, $isDeleted)
    {
        Notification::whereIn('id', $ids)->update(['is_deleted' => $isDeleted]);
    }

    /**
     * Adds a notification driver to the list.
     *
     * @param string $driverName
     * @param NotificationDriverInterface $driver
     */
    public static function addNotificationDriver(string $driverName, NotificationDriverInterface $driver)
    {
        static::$notificationDrivers[$driverName] = $driver;
    }

    /**
     * @return NotificationDriverInterface[]
     */
    public static function getNotificationDrivers(): array
    {
        return static::$notificationDrivers;
    }

    /**
     * @param callable|string $callback
     */
    public static function beforeSending($callback): void
    {
        static::$beforeSendingCallbacks[] = $callback;
    }
}
