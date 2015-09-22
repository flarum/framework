<?php

namespace Flarum\Migrations\Core;

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class RenameNotificationReadTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('notification_read_time', 'notifications_read_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('notifications_read_time', 'notification_read_time');
        });
    }
}
