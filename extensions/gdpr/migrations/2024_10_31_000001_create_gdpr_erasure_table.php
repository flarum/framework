<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (!$schema->hasTable('gdpr_erasure')) {
            $schema->create('gdpr_erasure', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->nullable()->unique();
                $table->string('verification_token');
                $table->string('status')->nullable();
                $table->text('reason')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('user_confirmed_at')->nullable();
                $table->integer('processed_by')->unsigned()->nullable();
                $table->text('processor_comment')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->string('processed_mode')->nullable();

                $table->foreign('processed_by')->references('id')->on('users')->onDelete('no action');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('gdpr_erasure');
    },
];
