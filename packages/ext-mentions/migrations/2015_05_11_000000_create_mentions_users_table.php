<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class CreateMentionsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('mentions_users', function (Blueprint $table) {
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
        $this->schema->drop('mentions_users');
    }
}
