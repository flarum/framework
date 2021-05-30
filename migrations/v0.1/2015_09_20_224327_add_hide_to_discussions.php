<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    'hide_time' => ['dateTime', 'nullable' => true],
    'hide_user_id' => ['integer', 'unsigned' => true, 'nullable' => true]
]);
