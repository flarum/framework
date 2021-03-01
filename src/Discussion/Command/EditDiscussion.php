<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Flarum\User\User;

class EditDiscussion
{
    /**
     * The ID of the discussion to edit.
     *
     * @var int
     */
    public $discussionId;

    /**
     * The user performing the action.
     *
     * @var \Flarum\User\User
     */
    public $actor;

    /**
     * The attributes to update on the discussion.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $discussionId The ID of the discussion to edit.
     * @param \Flarum\User\User $actor The user performing the action.
     * @param array $data The attributes to update on the discussion.
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
