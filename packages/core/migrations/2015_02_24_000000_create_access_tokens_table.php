<?php

use Flarum\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccessTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->integer('user_id')->unsigned();
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop('access_tokens');
    }
}
