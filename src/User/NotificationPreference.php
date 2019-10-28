<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Database\AbstractModel;
use InvalidArgumentException;

/**
 * @property int $user_id
 * @property string $type
 * @property string $channel
 * @property bool $enabled
 */
class NotificationPreference extends AbstractModel
{
    static protected $channels = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function addChannel(string $channel)
    {
        static::$channels[] = $channel;
    }

    public static function setNotificationPreference(User $user, string $type, string $channel, bool $toggle = true)
    {
        if (in_array($channel, static::$channels)) {
            $user->notificationPreferences()
                ->where('channel', $channel)
                ->where('type', $type)
                ->update(['enabled' => $toggle]);
        } else {
            throw new InvalidArgumentException("Channel is not registered.");
        }
    }
}
