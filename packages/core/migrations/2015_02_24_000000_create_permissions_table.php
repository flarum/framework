<?php

use Flarum\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePermissionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('permissions', function (Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->string('permission', 100);
            $table->primary(['group_id', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('permissions');
    }
}
