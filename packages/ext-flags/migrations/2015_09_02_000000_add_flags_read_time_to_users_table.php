<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AddFlagsReadTimeToUsersTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->dateTime('flags_read_time')->nullable();
        });
    }

    public function down()
    {
        $this->schema->drop('flags_read_time');
    }
}
