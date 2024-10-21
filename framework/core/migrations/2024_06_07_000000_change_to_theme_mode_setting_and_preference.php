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
        $darkMode = $schema->getConnection()
            ->table('settings')
            ->where('key', 'theme_dark_mode')
            ->first();

        $schema->getConnection()
            ->table('settings')
            ->insert([
                [
                    'key' => 'color_scheme',
                    'value' => $darkMode === '1' ? 'dark' : 'auto',
                ],
                [
                    'key' => 'allow_user_color_scheme',
                    'value' => '1',
                ]
            ]);

        $schema->getConnection()
            ->table('settings')
            ->where('key', 'theme_dark_mode')
            ->delete();
    },

    'down' => function (Builder $schema) {
        $themeMode = $schema->getConnection()
            ->table('settings')
            ->where('key', 'color_scheme')
            ->first();

        $schema->getConnection()
            ->table('settings')
            ->insert([
                'key' => 'theme_dark_mode',
                'value' => $themeMode === 'dark' ? '1' : '0',
            ]);

        $schema->getConnection()
            ->table('settings')
            ->whereIn('key', ['color_scheme', 'allow_user_color_scheme'])
            ->delete();
    }
];
