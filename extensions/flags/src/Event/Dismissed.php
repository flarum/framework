<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Event;

use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Support\Collection;

class Dismissed
{
    /**
     * @param Collection<int, Flag> $flags
     */
    public function __construct(
        public Post $post,
        public Collection $flags,
        public User $actor,
        public array $data = []
    ) {
    }
}
