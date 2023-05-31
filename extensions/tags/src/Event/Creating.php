<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Event;

use Flarum\Tags\Tag;
use Flarum\User\User;

class Creating
{
    public function __construct(
        public Tag $tag,
        public User $actor,
        public array $data
    ) {
    }
}
