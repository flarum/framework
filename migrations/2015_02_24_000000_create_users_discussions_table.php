<?php

use Flarum\Install\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersDiscussionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('users_discussions', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('discussion_id')->unsigned();
            $table->dateTime('read_time')->nullable();
            $table->integer('read_number')->unsigned()->nullable();
            $table->primary(['user_id', 'discussion_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('users_discussions');
    }
}
