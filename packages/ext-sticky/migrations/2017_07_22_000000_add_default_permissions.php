<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Core\Group;
use Flarum\Database\Migration;

return Migration::addPermissions([
    'discussion.sticky' => Group::MODERATOR_ID
]);
