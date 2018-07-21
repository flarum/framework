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
    'up' => function (Builder $schema) {
        $query = $schema->getConnection()->table('notifications')
            ->whereExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereRaw('id = sender_id');
            });

        foreach ($query->cursor() as $notification) {
            $insert = [
                'id' => $notification->id,
                'from_user_id' => $notification->sender_id
            ];

            $schema->getConnection()->table('notifications_from')->updateOrInsert($insert, $insert);
        }
    },

    'down' => function (Builder $schema) {
        $schema->getConnection()->table('notifications_from')->truncate();
    }
];
