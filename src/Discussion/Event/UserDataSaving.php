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
    /**
     * @var \Flarum\Discussion\UserState
     */
    public $state;

    /**
     * @param \Flarum\Discussion\UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
