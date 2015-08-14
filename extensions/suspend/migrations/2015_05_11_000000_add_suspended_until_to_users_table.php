<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddSuspendedUntilToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('users', function (Blueprint $table) {
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
        $this->schema->table('users', function (Blueprint $table) {
            $table->dropColumn('suspended_until');
        });
    }
}
