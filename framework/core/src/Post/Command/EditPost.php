<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Flarum\User\User;

class EditPost
{
    public function __construct(
        public int $postId,
        public User $actor,
        public array $data
    ) {
    }
}
