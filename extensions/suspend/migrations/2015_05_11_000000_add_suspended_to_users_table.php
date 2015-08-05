<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSuspendedUntilToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app('db')->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->dateTime('suspended_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        app('db')->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->dropColumn('suspended_until');
        });
    }
}
