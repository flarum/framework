<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('subject_type');

            $table->renameColumn('time', 'created_at');
            $table->renameColumn('sender_id', 'from_user_id');

            $table->dateTime('read_at')->nullable();
        });

        $schema->getConnection()->table('notifications')
            ->where('is_read', 1)
            ->update(['read_at' => Carbon::now()]);

        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('notifications', function (Blueprint $table) {
            $table->string('subject_type', 200)->nullable();

            $table->renameColumn('created_at', 'time');
            $table->renameColumn('from_user_id', 'sender_id');

            $table->boolean('is_read');
        });

        $schema->getConnection()->table('notifications')
            ->whereNotNull('read_at')
            ->update(['is_read' => 1]);

        $schema->table('notifications', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
];
