<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::renameColumns('discussion_user', [
    'read_time' => 'last_read_at',
    'read_number' => 'last_read_post_number'
]);
