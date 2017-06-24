<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\UserState;

class UserRead
{
    /**
     * @var UserState
     */
    public $state;

    /**
     * @param UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
