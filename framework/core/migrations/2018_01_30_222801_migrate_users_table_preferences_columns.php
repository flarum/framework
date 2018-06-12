<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;

return [
    'up' => function (Builder $builder) {
        $db = $builder->getConnection();

        $db->table('users')
            ->whereNotNull('preferences')
            ->orderBy('id')
            ->chunk(50, function (Collection $users) use ($db) {
                $users->each(function ($user) use ($db) {
                    collect(json_decode(Arr::get($user, 'preferences', '{}')))
                        ->each(function ($value, $key) use ($user, $db) {
                            if ($key === 'discloses_online') {
                                $db->table('users')
                                    ->where('id', $user['id'])
                                    ->update(['discloses_online' => (bool) $value]);
                            }
                            if (preg_match('/^notify_(?<type>[^_]+)_(?<channel>.*)$/', $key, $matches)) {
                                $db->table('notification_preferences')
                                    ->insert([
                                        'user_id' => $user['id'],
                                        'type' => $matches['type'],
                                        'channel' => $matches['channel'],
                                        'enabled' => (bool) $value
                                    ]);
                            }
                        });
                });
            });
    },

    'down' => function (Builder $builder) {
        $db = $builder->getConnection();

        $db->table('notification_preferences')->truncate();
    }
];
