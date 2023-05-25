<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\User\User;

class DeleteFlags
{
    public function __construct(
        public int $postId,
        public User $actor,
        public array $data = []
    ) {}
}
