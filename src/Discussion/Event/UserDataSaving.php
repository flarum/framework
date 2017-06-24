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

use Flarum\Core\DiscussionState;

class UserDataSaving
{
    /**
     * @var DiscussionState
     */
    public $state;

    /**
     * @param DiscussionState $state
     */
    public function __construct(DiscussionState $state)
    {
        $this->state = $state;
    }
}
