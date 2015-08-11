<?php

use Flarum\Install\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('discussion_id')->unsigned();
            $table->integer('number')->unsigned()->nullable();

            $table->dateTime('time');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type', 100)->nullable();
            $table->text('content')->nullable();

            $table->dateTime('edit_time')->nullable();
            $table->integer('edit_user_id')->unsigned()->nullable();
            $table->dateTime('hide_time')->nullable();
            $table->integer('hide_user_id')->unsigned()->nullable();

            $table->unique(['discussion_id', 'number']);

            $table->engine = 'MyISAM';
        });

        $prefix = $this->schema->getConnection()->getTablePrefix();
        $this->schema->getConnection()->statement('ALTER TABLE '.$prefix.'posts ADD FULLTEXT content (content)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('posts');
    }
}
