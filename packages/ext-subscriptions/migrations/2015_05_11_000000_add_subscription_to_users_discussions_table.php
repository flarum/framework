<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddSubscriptionToUsersDiscussionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('users_discussions', function (Blueprint $table) {
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
        $this->schema->table('users_discussions', function (Blueprint $table) {
            $table->dropColumn('subscription');
        });
    }
}
