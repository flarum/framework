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
        if ($schema->hasTable('audit_log')) {
            return;
        }

        // Forums upgrading from the kilowhat premium (kilowhat-audit-pro) or free
        // (kilowhat-audit-free) extension already have a populated table. Both shipped the
        // exact same schema under the name `kilowhat_audit_log`, and they `replace` each other
        // so only one is ever present at a time. Rename it in place to preserve every log entry
        // along with the primary key sequence / auto-increment value, rather than creating a new
        // table and copying rows.
        if ($schema->hasTable('kilowhat_audit_log')) {
            $schema->rename('kilowhat_audit_log', 'audit_log');

            return;
        }

        $schema->create('audit_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('actor_id')->nullable()->index();
            $table->string('client')->index();
            $table->string('ip_address')->nullable()->index();
            $table->string('action')->index();
            $table->json('payload')->nullable();
            $table->dateTime('created_at');

            // actor_id has no foreign constraints on purpose. We want to preserve deleted user IDs.
        });
    },
    'down' => function (Builder $schema) {
        // Don't delete the table, as it would be an easy way for an attacker to erase all logs.
        // Instead, the `audit:clear --reset` console command should be used to delete the data.
    },
];
