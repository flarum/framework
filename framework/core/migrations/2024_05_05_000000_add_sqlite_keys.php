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
        if ($schema->getConnection()->getDriverName() === 'sqlite') {
            $schema->getConnection()->statement('PRAGMA foreign_keys = OFF');
            $schema->getConnection()->statement('PRAGMA writable_schema = ON');

            $prefix = $schema->getConnection()->getTablePrefix();

            $foreignKeysSqlite = [
                'discussions' => <<<SQL
                    foreign key("user_id") references "{$prefix}users"("id") on delete set null,
                    foreign key("last_posted_user_id") references "{$prefix}users"("id") on delete set null,
                    foreign key("hidden_user_id") references "{$prefix}users"("id") on delete set null,
                    foreign key("first_post_id") references "{$prefix}posts"("id") on delete set null,
                    foreign key("last_post_id") references "{$prefix}posts"("id") on delete set null
                SQL,
                'posts' => <<<SQL
                    foreign key("discussion_id") references "{$prefix}discussions"("id") on delete cascade,
                    foreign key("user_id") references "{$prefix}users"("id") on delete set null,
                    foreign key("edited_user_id") references "{$prefix}users"("id") on delete set null,
                    foreign key("hidden_user_id") references "{$prefix}users"("id") on delete set null
                SQL,
            ];

            foreach (['discussions', 'posts'] as $table) {
                $tableDefinition = $schema->getConnection()->select('SELECT sql FROM sqlite_master WHERE type = "table" AND name = "'.$prefix.$table.'"')[0]->sql;
                $modifiedTableDefinition = str($tableDefinition)->beforeLast(')')->append(', '.$foreignKeysSqlite[$table].')')->toString();
                $modifiedTableDefinitionWithEscapedQuotes = str($modifiedTableDefinition)->replace('"', '""')->toString();
                $schema->getConnection()->statement('UPDATE sqlite_master SET sql = "'.$modifiedTableDefinitionWithEscapedQuotes.'" WHERE type = "table" AND name = "'.$prefix.$table.'"');
            }

            $schema->getConnection()->statement('PRAGMA writable_schema = OFF');
            $schema->getConnection()->statement('PRAGMA foreign_keys = ON');
        }
    },
];
