<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => static function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'discussions ADD FULLTEXT title (title)');
    },

    'down' => static function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement('ALTER TABLE '.$prefix.'discussions DROP INDEX title');
    }
];
