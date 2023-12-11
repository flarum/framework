<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('package_manager_tasks', [
    'guessed_cause' => ['type' => 'string', 'length' => 255, 'nullable' => true, 'after' => 'output'],
]);
