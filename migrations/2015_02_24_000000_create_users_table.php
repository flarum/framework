<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 100)->unique();
            $table->string('email', 150)->unique();
            $table->boolean('is_activated')->default(0);
            $table->string('password', 100);
            $table->string('locale', 10)->default('en');
            $table->text('bio')->nullable();
            $table->text('bio_html')->nullable();
            $table->string('avatar_path', 100)->nullable();
            $table->binary('preferences')->nullable();
            $table->dateTime('join_time')->nullable();
            $table->dateTime('last_seen_time')->nullable();
            $table->dateTime('read_time')->nullable();
            $table->dateTime('notification_read_time')->nullable();
            $table->integer('discussions_count')->unsigned()->default(0);
            $table->integer('comments_count')->unsigned()->default(0);
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
