<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'generic_tasks',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('status', 50)->nullable();
        $table->string('command', 50);
        $table->string('command_class')->nullable();
        $table->string('package', 100)->nullable();
        $table->mediumText('output');
        $table->dateTime('created_at');
        $table->dateTime('started_at')->nullable();
        $table->dateTime('finished_at')->nullable();
    }
);
