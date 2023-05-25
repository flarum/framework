<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Event;

use Flarum\Flags\Flag;
use Flarum\User\User;

class Created
{
    public function __construct(
        public Flag $flag,
        public User $actor,
        public array $data = []
    ) {
    }
}
