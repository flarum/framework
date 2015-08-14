<?php

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddStickyToDiscussions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->boolean('is_sticky')->default(0);
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
            $table->dropColumn('is_sticky');
        });
    }
}
