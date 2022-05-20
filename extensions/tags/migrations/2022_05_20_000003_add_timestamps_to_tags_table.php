<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('tags', [
    'created_at' => [
        'timestamp',
        'null' => true,
        'useCurrent' => true,
    ],
    'updated_at' => [
        'timestamp',
        'null' => true,
        'useCurrent' => true,
        'useCurrentOnUpdate' => true,
    ],
]);
