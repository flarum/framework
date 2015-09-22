<?php

namespace Flarum\Migrations\Core;

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddHideToDiscussions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dateTime('hide_time')->nullable();
            $table->integer('hide_user_id')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn(['hide_time', 'hide_user_id']);
        });
    }
}
