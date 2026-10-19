<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * An idea is a discussion with an ideation status, so the state lives on the
 * discussion itself. The board's every query is "ideas in this status, most
 * voted first", which is exactly the composite index below: no join, no
 * filesort, however many ideas there are. The Shipped column sorts by when
 * an idea got there, which is the second.
 *
 * The vote count is kept here rather than counted per request. It is changed
 * only by atomic increments and decrements next to the vote row itself.
 */
/*
 * 🚨 Index names are given, not generated: the generated ones are prefix +
 * table + every column, and "flarum_c_discussions_ideation_status_ideation_status_changed_at_index"
 * is over MariaDB's 64-character limit. The prefix is kept in them because
 * PostgreSQL index names are per schema, not per table.
 */
$names = fn (Builder $schema) => [
    $schema->getConnection()->getTablePrefix().'discussions_ideation_votes',
    $schema->getConnection()->getTablePrefix().'discussions_ideation_moved',
];

return [
    'up' => function (Builder $schema) use ($names) {
        [$votes, $moved] = $names($schema);

        $schema->table('discussions', function (Blueprint $table) use ($votes, $moved) {
            $table->string('ideation_status', 20)->nullable();
            $table->unsignedInteger('ideation_votes')->default(0);
            $table->unsignedInteger('ideation_merged_into_id')->nullable();
            $table->dateTime('ideation_status_changed_at')->nullable();

            $table->index(['ideation_status', 'ideation_votes'], $votes);
            $table->index(['ideation_status', 'ideation_status_changed_at'], $moved);
            $table->foreign('ideation_merged_into_id')->references('id')->on('discussions')->nullOnDelete();
        });
    },
    'down' => function (Builder $schema) use ($names) {
        [$votes, $moved] = $names($schema);

        $schema->table('discussions', function (Blueprint $table) use ($votes, $moved) {
            $table->dropForeign(['ideation_merged_into_id']);
            $table->dropIndex($votes);
            $table->dropIndex($moved);
            $table->dropColumn(['ideation_status', 'ideation_votes', 'ideation_merged_into_id', 'ideation_status_changed_at']);
        });
    },
];
