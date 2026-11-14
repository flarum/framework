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
 * `ip_hex` holds each entry's IP address in the fixed-length form described on
 * `Flarum\Audit\IpRange`, so the `ip:` filter can match partial addresses and
 * subnets with an indexed range query.
 *
 * Existing entries are deliberately not filled in here: an audit log can hold
 * millions of rows, and doing it inline would stall the upgrade. Until an admin
 * runs `php flarum audit:backfill-ip`, those entries are still found by an
 * exact `ip:` search, but not by a partial or subnet one.
 */
return [
    'up' => function (Builder $schema) {
        // The column and its index are guarded separately: on MySQL and MariaDB
        // they are two ALTER statements that can't be rolled back together, so
        // if adding the index fails on a large table, running the migration
        // again must still add it.
        if (! $schema->hasColumn('audit_log', 'ip_hex')) {
            $schema->table('audit_log', function (Blueprint $table) {
                $table->char('ip_hex', 32)->nullable();
            });
        }

        if (! $schema->hasIndex('audit_log', ['ip_hex'])) {
            $schema->table('audit_log', function (Blueprint $table) {
                $table->index(['ip_hex']);
            });
        }
    },
    'down' => function (Builder $schema) {
        // Deliberately left in place, like the table itself. Uninstalling the
        // extension rolls its migrations back and then logs
        // `extension.uninstalled` through the still-loaded `AuditLog`, which
        // writes `ip_hex`; dropping the column here would make that insert fail.
    },
];
