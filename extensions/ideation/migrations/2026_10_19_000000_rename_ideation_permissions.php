<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/*
 * Voting and moderating become `discussion.*` permissions, so the tags
 * extension can grant them per restricted tag. Existing grants are carried
 * over to the new names, and back again on rollback.
 *
 * Posting ideas keeps its name: it is checked before the discussion (and so
 * its tags) exists, and tags already decide where a discussion may be
 * started.
 */
$names = [
    'ideation.vote' => 'discussion.ideationVote',
    'ideation.moderate' => 'discussion.ideationModerate',
];

$rename = function (Builder $schema, array $names): void {
    $db = $schema->getConnection();

    foreach ($names as $from => $to) {
        // A group already holding both names keeps one row. The ids are read
        // first: MySQL cannot delete from a table it is selecting from.
        $groups = $db->table('group_permission')->where('permission', $from)->pluck('group_id')->all();

        $db->table('group_permission')->where('permission', $to)->whereIn('group_id', $groups)->delete();

        $db->table('group_permission')->where('permission', $from)->update(['permission' => $to]);
    }
};

return [
    'up' => fn (Builder $schema) => $rename($schema, $names),
    'down' => fn (Builder $schema) => $rename($schema, array_flip($names)),
];
