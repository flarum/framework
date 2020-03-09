<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Str;

return [
    'up' => function (Builder $builder) {
        $db = $builder->getConnection();

        $db->table('users')
            ->select(['id', 'preferences'])
            ->whereNotNull('preferences')
            ->orderBy('id')
            ->each(function ($user) use ($db) {
                collect(json_decode($user->preferences ?? '{}'))
                    ->each(function ($value, $key) use ($user, $db) {
                        if (in_array($key, ['discloseOnline', 'followAfterReply'])) {
                            $db->table('users')
                                ->where('id', $user->id)
                                ->update([Str::snake($key) => (bool) $value]);
                        }
                        if ($key === 'locale') {
                            $db->table('users')
                                ->where('id', $user->id)
                                ->update(['locale' => $value]);
                        }
                        if (preg_match('/^notify_(?<type>[^_]+)_(?<channel>.*)$/', $key, $matches)) {
                            $db->table('notification_preferences')
                                ->insert([
                                    'user_id' => $user->id,
                                    'type' => $matches['type'],
                                    'channel' => $matches['channel'],
                                    'enabled' => (bool) $value
                                ]);
                        }
                    });
            });
    },

    'down' => function (Builder $builder) {
        $db = $builder->getConnection();

        $db->table('notification_preferences')->truncate();
    }
];
