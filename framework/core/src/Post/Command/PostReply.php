<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Flarum\User\User;

class PostReply
{
    public function __construct(
        public int $discussionId,
        public User $actor,
        public array $data,
        public ?string $ipAddress = null,
        public bool $isFirstPost = false
    ) {
    }
}
