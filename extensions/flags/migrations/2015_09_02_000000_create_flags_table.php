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
        $schema->create('flags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->string('type');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('reason')->nullable();
            $table->string('reason_detail')->nullable();
            $table->dateTime('time');
        });
    },

    'down' => function (Builder $schema) {
        $schema->drop('flags');
    }
];
