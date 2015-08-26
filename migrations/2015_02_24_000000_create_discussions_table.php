<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDiscussionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('discussions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 200);
            $table->integer('comments_count')->unsigned()->default(0);
            $table->integer('participants_count')->unsigned()->default(0);
            $table->integer('number_index')->unsigned()->default(0);

            $table->dateTime('start_time');
            $table->integer('start_user_id')->unsigned()->nullable();
            $table->integer('start_post_id')->unsigned()->nullable();

            $table->dateTime('last_time')->nullable();
            $table->integer('last_user_id')->unsigned()->nullable();
            $table->integer('last_post_id')->unsigned()->nullable();
            $table->integer('last_post_number')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('discussions');
    }
}
