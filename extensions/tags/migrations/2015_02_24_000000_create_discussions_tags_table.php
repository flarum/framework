<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class CreateDiscussionsTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('discussions_tags', function (Blueprint $table) {
            $table->integer('discussion_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->primary(['discussion_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('discussions_tags');
    }
}
