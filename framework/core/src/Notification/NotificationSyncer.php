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
     * Whether notifications are being limited to one per user.
     */
    protected static bool $onePerUser = false;

    /**
     * An internal list of user IDs that notifications have been sent to.
     *
     * @var int[]
     */
    protected static array $sentTo = [];

    /**
     * A map of notification drivers.
     *
     * @var NotificationDriverInterface[]
     */
    protected static array $notificationDrivers = [];

    /**
     * @var callable[]
     */
    protected static array $beforeSendingCallbacks = [];

    /**
     * Sync a notification so that it is visible to the specified users, and not
     * visible to anyone else. If it is being made visible for the first time,
     * attempt to send the user an email.
     *
     * @param User[] $users
     */
    public function sync(BlueprintInterface $blueprint, array $users): void
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

            /** @var Notification|null $existing */
            $existing = $toDelete->first(function (Notification $notification) use ($user) {
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

        // Delete all the remaining notification records which weren't
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
        foreach (static::getNotificationDrivers() as $driver) {
            $driver->send($blueprint, $newRecipients);
        }
    }

    /**
     * Delete a notification for all users.
     */
    public function delete(BlueprintInterface $blueprint): void
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => true]);
    }

    /**
     * Restore a notification for all users.
     */
    public function restore(BlueprintInterface $blueprint): void
    {
        Notification::matchingBlueprint($blueprint)->update(['is_deleted' => false]);
    }

    /**
     * Limit notifications to one per user for the entire duration of the given
     * callback.
     * @todo: useless when using a queue. replace with a better solution.
     */
    public function onePerUser(callable $callback): void
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
     */
    protected function setDeleted(array $ids, bool $isDeleted): void
    {
        Notification::query()->whereIn('id', $ids)->update(['is_deleted' => $isDeleted]);
    }

    /**
     * @internal
     */
    public static function addNotificationDriver(string $driverName, NotificationDriverInterface $driver): void
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
     * @internal
     */
    public static function beforeSending(callable $callback): void
    {
        static::$beforeSendingCallbacks[] = $callback;
    }
}
