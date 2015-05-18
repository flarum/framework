<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('notifications', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->integer('sender_id')->unsigned()->nullable();
            $table->string('type', 100);
            $table->string('subject_type', 200)->nullable();
			$table->integer('subject_id')->unsigned()->nullable();
			$table->binary('data')->nullable();
			$table->dateTime('time');
			$table->boolean('is_read')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('notifications');
	}

}
