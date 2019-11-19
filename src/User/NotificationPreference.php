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
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * @property int $user_id
 * @property string $type
 * @property string $channel
 * @property bool $enabled
 */
class NotificationPreference extends AbstractModel
{
    protected static $channels = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function addChannel(string $channel)
    {
        static::$channels[] = $channel;
    }

    public static function setNotificationPreference(User $user, string $type, string $channel, bool $enabled = true)
    {
        if (in_array($channel, static::$channels)) {
            $attributes = [
                'channel' => $channel,
                'type' => $type
            ];

            $user->notificationPreferences()->updateOrInsert($attributes, ['enabled' => $enabled]);
        } else {
            throw new InvalidArgumentException("Channel '$channel' is not registered.");
        }
    }

    public function scopeShouldBeNotified(Builder $query, string $type, string $channel = null)
    {
        return $query
            ->where('enabled', true)
            ->where('type', $type)
            ->when($channel, function ($query, $channel) {
                $query->where('channel', $channel);
            });
    }
}
