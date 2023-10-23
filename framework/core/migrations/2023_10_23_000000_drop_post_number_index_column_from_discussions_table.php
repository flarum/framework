<?php

use Flarum\Database\Migration;

return Migration::dropColumns('discussions', ['post_number_index']);
