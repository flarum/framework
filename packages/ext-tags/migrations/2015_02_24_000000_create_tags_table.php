<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();

            $table->string('color', 50)->nullable();
            $table->string('background_path', 100)->nullable();
            $table->string('background_mode', 100)->nullable();
            $table->string('icon_path', 100)->nullable();

            $table->integer('position')->nullable();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('default_sort', 50)->nullable();

            $table->integer('discussions_count')->unsigned()->default(0);
            $table->integer('last_time')->unsigned()->nullable();
            $table->integer('last_discussion_id')->unsigned()->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tags');
    }
}
