<?php

use Flarum\Database\Migration;

return Migration::addColumns('package_manager_tasks', [
    'guessed_cause' => ['type' => 'string', 'length' => 255, 'nullable' => true, 'after' => 'output'],
]);
