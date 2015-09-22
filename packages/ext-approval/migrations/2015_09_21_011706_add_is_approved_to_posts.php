<?php

namespace Flarum\Migrations\Approval;

use Illuminate\Database\Schema\Blueprint;
use Flarum\Migrations\Migration;

class AddIsApprovedToPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->boolean('is_approved')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
}
