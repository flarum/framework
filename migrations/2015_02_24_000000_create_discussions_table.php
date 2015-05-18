<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscussionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussions', function (Blueprint $table) {
        
            $table->increments('id');
            $table->string('title', 200);
            $table->integer('comments_count')->unsigned()->default(0);
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
        Schema::drop('discussions');
    }
}
