<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

// Forums upgrading from 2.0 keep opening in the browser until an admin turns
// "Open as an app" on: it registers a service worker and changes how the forum
// opens from the home screen. New installs turn it on in WriteSettings, which
// runs after the migrations.
return [
    'up' => function (Builder $schema) {
        $schema->getConnection()->table('settings')->insertOrIgnore([
            'key' => 'open_as_app',
            'value' => '0',
        ]);
    },

    'down' => function (Builder $schema) {
        $schema->getConnection()->table('settings')->where('key', 'open_as_app')->delete();
    }
];
