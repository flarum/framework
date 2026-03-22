<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => static function (Builder $schema) {
        $connection = $schema->getConnection();

        // Delete any stored CDN settings that reference the old default CDN URL
        $connection->table('settings')
            ->where('key', 'flarum-emoji.cdn')
            ->where('value', 'like', 'https://cdn.jsdelivr.net/gh/twitter/twemoji%')
            ->delete();
    },

    'down' => function (Builder $schema) {
        // noop
    }
];
