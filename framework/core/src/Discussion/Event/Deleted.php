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

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class Deleted
{
    /**
     * @var \Flarum\Discussion\Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param User $actor
     */
    public function __construct(Discussion $discussion, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
    }
}
