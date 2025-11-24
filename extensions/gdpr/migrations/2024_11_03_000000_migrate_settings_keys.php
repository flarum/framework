<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

$settingsKeys = [
    'allow-anonymization',
    'allow-deletion',
    'default-erasure',
    'default-anonymous-username',
];

return [
    'up' => function (Builder $schema) use ($settingsKeys) {
        $db = $schema->getConnection();

        foreach ($settingsKeys as $setting) {
            $db->table('settings')
                ->where('key', "blomstra-gdpr.$setting")
                ->update(['key' => "flarum-gdpr.$setting"]);
        }
    },
    'down' => function (Builder $schema) use ($settingsKeys) {
        $db = $schema->getConnection();

        foreach ($settingsKeys as $setting) {
            $db->table('settings')
                ->where('key', "flarum-gdpr.$setting")
                ->update(['key' => "blomstra-gdpr.$setting"]);
        }
    },
];
