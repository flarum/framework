<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Event;

use Flarum\Post\Post;
use Flarum\User\User;

/**
 * Some or all of a post's flags are gone: dismissed, the post deleted, or the post
 * approved (its approval or Akismet flag). Fired once per removal, after the flags are gone,
 * unlike Deleting, which fires for each dismissed flag beforehand.
 */
class Cleared
{
    public function __construct(
        public Post $post,
        public ?User $actor = null
    ) {
    }
}
