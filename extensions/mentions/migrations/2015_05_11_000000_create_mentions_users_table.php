<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMentionsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mentions_users', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->integer('mentions_id')->unsigned();
            $table->primary(['post_id', 'mentions_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mentions_users');
    }
}
