<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Flarum\Post\CommentPost;
use Flarum\User\User;

class Revised
{
    public function __construct(
        public CommentPost $post,
        public User $actor,
        public string $oldContent
    ) {
    }
}
