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
        $schema->table('discussions', function (Blueprint $table) {
            // Supports sorting the discussion list by title, which the A-Z and
            // Z-A options in the sort dropdown do.
            //
            // There is already an index on this column, but it is a FULLTEXT
            // one: an inverted list of the words each title contains, which
            // answers "which discussions mention coffee" and has no idea where
            // a whole title falls alphabetically. MySQL will not consider it
            // for ORDER BY at all — the plan comes back as a full table scan
            // with a filesort, and stays that way however large the forum
            // grows. On 200,000 discussions that is the difference between
            // roughly 300ms and 2ms for a single page.
            //
            // One index covers both directions: the descending sort is served
            // by reading the same index backwards.
            $table->index('title');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropIndex(['title']);
        });
    }
];
