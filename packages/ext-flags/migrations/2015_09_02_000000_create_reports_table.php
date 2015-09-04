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
use Flarum\Migrations\Migration;

class CreateReportsTable extends Migration
{
    public function up()
    {
        $this->schema->create('reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('reporter')->nullable();
            $table->string('reason')->nullable();
            $table->string('reason_detail')->nullable();
            $table->dateTime('time');
        });
    }

    public function down()
    {
        $this->schema->drop('reports');
    }
}
