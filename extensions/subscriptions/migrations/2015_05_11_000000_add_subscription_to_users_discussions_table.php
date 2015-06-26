<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionToUsersDiscussionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app('db')->getSchemaBuilder()->table('users_discussions', function (Blueprint $table) {
            $table->enum('subscription', ['follow', 'ignore'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        app('db')->getSchemaBuilder()->table('users_discussions', function (Blueprint $table) {
            $table->dropColumn('subscription');
        });
    }
}
