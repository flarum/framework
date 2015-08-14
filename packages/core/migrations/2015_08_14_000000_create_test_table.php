<?php

use Flarum\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('test', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('test');
    }
}
