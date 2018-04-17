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
        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('sender_id', 'subject_type');

            $table->renameColumn('time', 'created_at');

            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        $schema->getConnection()->table('notifications')
            ->where('is_read', 1)
            ->update(['read_at' => time()]);

        $schema->getConnection()->table('notifications')
            ->where('is_deleted', 1)
            ->update(['deleted_at' => time()]);

        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('is_read');
            $table->dropColumn('is_deleted');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('notifications', function (Blueprint $table) {
            $table->integer('sender_id')->unsigned()->nullable();
            $table->string('subject_type', 200)->nullable();

            $table->renameColumn('created_at', 'time');

            $table->boolean('is_read');
            $table->boolean('is_deleted');
        });

        $schema->getConnection()->table('notifications')
            ->whereNotNull('read_at')
            ->update(['is_read' => 1]);
        $schema->getConnection()->table('notifications')
            ->whereNotNull('deleted_at')
            ->update(['is_deleted' => 1]);

        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('read_at');
            $table->dropColumn('deleted_at');
        });
    }
];
