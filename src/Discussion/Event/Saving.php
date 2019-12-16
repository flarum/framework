<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class Saving
{
    /**
     * The discussion that will be saved.
     *
     * @var \Flarum\Discussion\Discussion
     */
    public $discussion;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * Any user input associated with the command.
     *
     * @var array
     */
    public $data;

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param User $actor
     * @param array $data
     */
    public function __construct(Discussion $discussion, User $actor, array $data = [])
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
        $this->data = $data;
    }
}
