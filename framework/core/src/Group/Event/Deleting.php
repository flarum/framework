<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Event;

use Flarum\Group\Group;
use Flarum\User\User;

class Deleting
{
    public function __construct(
        public Group $group,
        public User $actor,
        public array $data
    ) {
    }
}
