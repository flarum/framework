<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Event;

use Flarum\Post\Post;
use Flarum\User\User;

class PostWasApproved
{
    public function __construct(
        public Post $post,
        public User $actor
    ) {
    }
}
