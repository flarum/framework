<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

// One row per AI call: who and what it was for, and the tokens it used.
// Never the prompt or the reply.
return Migration::createTable(
    'ai_usage',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('feature', 100)->nullable();
        // Who the call was made for: "member", "guest" or "system". Null when
        // it was made outside a feature.
        $table->string('actor', 10)->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->string('connection', 100);
        $table->string('driver', 50);
        $table->string('model', 100)->nullable();
        $table->string('kind', 20);
        $table->unsignedInteger('input_tokens')->default(0);
        $table->unsignedInteger('output_tokens')->default(0);
        $table->dateTime('created_at');

        $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

        $table->index(['connection', 'created_at']);
        $table->index(['feature', 'created_at']);
        $table->index(['user_id', 'created_at']);
        $table->index(['actor', 'created_at']);
        $table->index('created_at');
    }
);
