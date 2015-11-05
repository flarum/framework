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

class CreateSessionsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('sessions', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('csrf_token', 40);
            $table->integer('last_activity');
            $table->integer('duration');
            $table->dateTime('sudo_expiry_time');
        });
    }

    public function down()
    {
        $this->schema->drop('sessions');
    }
}
