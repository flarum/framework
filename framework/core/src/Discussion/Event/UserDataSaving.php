<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\UserState;

class UserDataSaving
{
    public function __construct(
        public UserState $state
    ) {
    }
}
