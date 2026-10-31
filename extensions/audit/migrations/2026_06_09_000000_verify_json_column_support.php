<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/*
 * This migration previously aborted installation on MySQL/MariaDB versions too old to
 * support native JSON columns (MySQL < 5.7.8 / MariaDB < 10.2.7). Flarum 2.0 already
 * enforces a minimum of MySQL 5.7 / MariaDB 10.3 at install time (see
 * Flarum\Install\Steps\ConnectToDatabase), which covers the JSON requirement, and 2.0
 * additionally supports SQLite and PostgreSQL — where the old `select version()` probe
 * was invalid. The check is therefore obsolete.
 *
 * The migration is kept (rather than deleted) as a no-op so that existing installs which
 * already recorded it as run have a matching file to roll back against; removing it would
 * leave an orphaned migration row with no `down` to reverse.
 */
return [
    'up' => function (Builder $schema) {
        // No-op. See file header.
    },
    'down' => function (Builder $schema) {
        // No-op. See file header.
    },
];
