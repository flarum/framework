<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\User\User;

class CreateFlag
{
    public function __construct(
        public User $actor,
        public array $data
    ) {}
}
