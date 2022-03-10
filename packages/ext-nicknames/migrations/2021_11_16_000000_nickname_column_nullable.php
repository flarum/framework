<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->string('nickname')->nullable()->change();
        });
    },
    'down' => function (Builder $schema) {
    }
];
