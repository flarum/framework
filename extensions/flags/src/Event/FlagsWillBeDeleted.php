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
 * @deprecated v2.0
 * Listen for Flarum\Flags\Event\Deleting instead
 */
class FlagsWillBeDeleted
{
    public function __construct(
        public Post $post,
        public User $actor,
        public array $data = []
    ) {
    }
}
