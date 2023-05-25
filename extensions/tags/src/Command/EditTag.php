<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\User\User;

class EditTag
{
    public function __construct(
        public int $tagId,
        public User $actor,
        public array $data
    ) {
    }
}
