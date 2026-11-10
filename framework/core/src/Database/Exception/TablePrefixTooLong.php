<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Exception;

use Flarum\Database\DatabaseRequirements;
use RuntimeException;

class TablePrefixTooLong extends RuntimeException
{
    public function __construct(
        public string $prefix,
        public string $driver
    ) {
        $max = DatabaseRequirements::maxTablePrefixLength($driver);
        $limit = DatabaseRequirements::IDENTIFIER_LIMITS[$driver] ?? null;
        $length = strlen($prefix);

        parent::__construct(
            "The database table prefix '$prefix' is $length bytes long, but $driver allows at most $max. ".
            "Identifiers on $driver cannot exceed $limit, the longest index name Flarum's migrations generate is ".
            DatabaseRequirements::LONGEST_MIGRATION_IDENTIFIER.', and the prefix is prepended to index and foreign key '.
            'names as well as to table names. A longer prefix will stop migrations running. Shorten the `prefix` value '.
            'in config.php and rename the existing tables to match.'
        );
    }
}
