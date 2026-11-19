<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

// Saving a tag from the admin edit modal used to clear its is_primary flag.
// A tag's position is what makes it primary (primary tags and their children
// have one), so set the flag from it again.
//
// While flags were cleared, a new primary tag was placed after only the tags
// still flagged, so it could share a position with another. Number the
// positions in each list (the primary tags, and each one's children) again,
// keeping their order and breaking ties by creation.
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('tags')->whereNotNull('position')->update(['is_primary' => true]);
        $db->table('tags')->whereNull('position')->update(['is_primary' => false]);

        $db->table('tags')
            ->whereNotNull('position')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'position'])
            ->groupBy('parent_id')
            ->each(function ($tags) use ($db) {
                foreach ($tags->values() as $position => $tag) {
                    if ((int) $tag->position !== $position) {
                        $db->table('tags')->where('id', $tag->id)->update(['position' => $position]);
                    }
                }
            });
    },
    'down' => function (Builder $schema) {
        // The flags and positions it replaced were wrong, so there is nothing
        // to restore.
    },
];
