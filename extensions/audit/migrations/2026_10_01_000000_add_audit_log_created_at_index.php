<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * The log is always read newest-first — `AuditLogResource` sorts on
 * `-createdAt` by default and offers no other sort — but `created_at` was the
 * one column the table did not index, so every page of it sorted the whole
 * table. Measured on MariaDB with 500k rows, the first page read all 500,001
 * rows through a filesort in ~35ms; indexed it reads 24 and takes ~0.2ms. The
 * cost grows with the table, and an audit log only ever grows.
 *
 * `audit:clear`, which deletes by `created_at`, gets a range scan out of it too.
 */
return [
    'up' => function (Builder $schema) {
        // Checked by column rather than by name. A forum that hit the slow page
        // may already have added this index by hand, or carried one in on the
        // `kilowhat_audit_log` table the create migration renames in place, and
        // in neither case will it be called `audit_log_created_at_index`. MySQL
        // and MariaDB accept a second index over the same column without
        // complaint, so a name-based check would silently leave those forums
        // paying for two identical indexes on every write.
        if ($schema->hasIndex('audit_log', ['created_at'])) {
            return;
        }

        $schema->table('audit_log', function (Blueprint $table) {
            $table->index(['created_at']);
        });
    },
    'down' => function (Builder $schema) {
        // Both sides derive the same conventional name from the column, so this
        // drops exactly what `up()` created. That is also why it is guarded on
        // the name rather than the column: an index the forum already had is
        // called something else, and rolling back must not remove one this
        // migration never added.
        if (! $schema->hasIndex('audit_log', 'audit_log_created_at_index')) {
            return;
        }

        $schema->table('audit_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    },
];
