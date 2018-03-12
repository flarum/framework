<?php

use Flarum\Database\Migration;

return Migration::addColumns('auth_tokens', [
    'suggestions' => ['text', 'nullable' => true]
]);
