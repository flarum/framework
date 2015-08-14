<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class CreateUsersTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('users_tags', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->dateTime('read_time')->nullable();
            $table->boolean('is_hidden')->default(0);
            $table->primary(['user_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('users_tags');
    }
}
