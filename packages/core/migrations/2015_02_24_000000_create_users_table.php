<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
            $table->boolean('is_confirmed')->default(0);
            $table->string('confirmation_token')->nullable();
            $table->boolean('is_activated')->default(0);
            $table->string('password');
            $table->dateTime('join_time')->nullable();
            $table->dateTime('last_seen_time')->nullable();
            $table->dateTime('read_time')->nullable();
            $table->integer('discussions_count')->unsigned()->default(0);
            $table->integer('posts_count')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }

}
