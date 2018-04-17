<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('notifications_from', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->integer('from_user_id')->unsigned();

            $table->foreign('id')->references('id')->on('notifications')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        $schema->getConnection()->table('notifications')->chunkById(100, function ($notifications) use ($schema) {
            foreach ($notifications as $notification) {
                $insert = [
                    'id' => $notification->id,
                    'from_user_id' => $notification->sender_id
                ];

                $schema->getConnection()->table('notifications_from')->updateOrInsert($insert, $insert);
            }
        });
    },

    'down' => function (Builder $schema) {
        $schema->drop('notifications_from');
    }
];
