<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Foundation\Paths;
use Illuminate\Database\Schema\Builder;

/*
 * Earlier versions of this extension kept copies of auth.json and composer.json
 * beside them: auth.json.millwright-backup-<date> and
 * composer.json.millwright-backup-<date>, one per write and made with the
 * umask's mode, before it became the Extension Manager, and
 * auth.json.extension-manager-backup since. auth.json is no longer copied, and
 * the old copies were only removed the next time an admin saved a setting, or
 * not at all under the old name. auth.json holds credentials in plain text and
 * composer.json can, so this removes those copies once, on every forum. The
 * single composer.json.extension-manager-backup the current code keeps, with
 * the file's own mode, is left alone.
 *
 * Nothing in the database changes, and down() has nothing to put back.
 */
return [
    'up' => function (Builder $schema) {
        $base = resolve(Paths::class)->base;

        foreach (@scandir($base) ?: [] as $name) {
            if (preg_match('/^(auth\.json\.(millwright|extension-manager)|composer\.json\.millwright)-backup/', $name) && is_file($base.'/'.$name)) {
                @unlink($base.'/'.$name);
            }
        }
    },

    'down' => function (Builder $schema) {
        //
    },
];
