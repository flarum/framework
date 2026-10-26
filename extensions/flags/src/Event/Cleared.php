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
 * A post's flags are gone: dismissed, or the post deleted. Fired once per post,
 * after the flags have been removed, unlike Deleting, which fires for each flag
 * beforehand.
 */
class Cleared
{
    public function __construct(
        public Post $post,
        public ?User $actor = null
    ) {
    }
}
