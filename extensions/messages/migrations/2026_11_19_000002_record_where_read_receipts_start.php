<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

// Before this, mark-all moved every read position, so earlier positions
// aren't receipts.
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('settings')->updateOrInsert(
            ['key' => 'flarum-messages.read_receipts_after'],
            ['value' => (string) (int) $db->table('dialog_messages')->max('id')]
        );
    },
    'down' => function (Builder $schema) {
        $schema->getConnection()->table('settings')->where('key', 'flarum-messages.read_receipts_after')->delete();
    },
];
