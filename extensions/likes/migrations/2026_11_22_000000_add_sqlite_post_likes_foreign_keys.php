<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        // Only SQLite forums upgraded from 1.x need this. Flarum 1.8 pinned
        // Laravel 8, whose SQLite grammar compiled ALTER ... ADD FOREIGN KEY
        // to nothing, so the foreign keys added in 2018 never existed there.
        // Core rebuilt `discussions` and `posts` in
        // 2024_05_05_000000_add_sqlite_keys.php and left `post_likes` to this
        // extension. Later Laravel versions create the table with the keys,
        // and the other databases have had them since 2018.
        if ($db->getDriverName() !== 'sqlite') {
            return;
        }

        $prefix = $db->getTablePrefix();
        $table = $prefix.'post_likes';

        $definition = $db->selectOne('SELECT sql FROM sqlite_master WHERE type = "table" AND name = ?', [$table])?->sql;

        if ($definition === null) {
            return;
        }

        $foreignKeys = [
            'post_id' => 'foreign key("post_id") references "'.$prefix.'posts"("id") on delete cascade',
            'user_id' => 'foreign key("user_id") references "'.$prefix.'users"("id") on delete cascade',
        ];

        $missing = [];

        foreach ($foreignKeys as $column => $clause) {
            if (! preg_match(
                '/foreign\s+key\s*\(\s*["`]?'.preg_quote($column, '/').'["`]?\s*\)/i',
                $definition
            )) {
                $missing[] = $clause;
            }
        }

        if ($missing === []) {
            return;
        }

        // The keys cannot be added to rows that would violate them. Remove the
        // orphans first, as the 2018 migration did where the keys were created.
        $db->table('post_likes')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('posts')->whereColumn('id', 'post_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->delete();

        // SQLite has no ALTER TABLE for foreign keys, so the table definition
        // is edited in place, the way core's migration does it.
        $modified = str($definition)->beforeLast(')')->append(', '.implode(', ', $missing).')')->toString();
        $schemaVersion = (int) $db->selectOne('PRAGMA schema_version')->schema_version;
        $foreignKeysEnabled = (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys;

        $db->statement('PRAGMA foreign_keys = OFF');
        $db->statement('PRAGMA writable_schema = ON');
        $db->update('UPDATE sqlite_master SET sql = ? WHERE type = "table" AND name = ?', [$modified, $table]);

        // Without this the open connection keeps the old definition cached and
        // deletes in the same request still do not cascade.
        $db->statement('PRAGMA schema_version = '.($schemaVersion + 1));
        $db->statement('PRAGMA writable_schema = OFF');

        if ($foreignKeysEnabled) {
            $db->statement('PRAGMA foreign_keys = ON');
        }
    },
];
