<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Migrations\Flags;

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddFlagsReadTimeToUsersTable extends Migration
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
