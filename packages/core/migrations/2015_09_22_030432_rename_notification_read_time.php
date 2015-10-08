<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class RenameNotificationReadTime extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('notification_read_time', 'notifications_read_time');
        });
    }

    public function down()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('notifications_read_time', 'notification_read_time');
        });
    }
}
