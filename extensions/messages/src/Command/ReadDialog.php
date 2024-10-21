<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Command;

use Flarum\User\User;

class ReadDialog
{
    public function __construct(
        public int $dialogId,
        public User $actor,
        public int $lastReadMessageId
    ) {
    }
}
