<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

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
                        if ($key === 'discloseOnline') {
                            $db->table('users')
                                ->where('id', $user->id)
                                ->update(['disclose_online' => (bool) $value]);
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
