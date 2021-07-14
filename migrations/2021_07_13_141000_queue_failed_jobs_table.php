<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'queue_failed_jobs',
    function (Blueprint $table) {
        $table->id();
        $table->string('uuid')->unique();
        $table->text('connection')->nullable();
        $table->text('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    }
);
