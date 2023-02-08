<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Flarum\User\User;

class PostReply
{
    /**
     * The ID of the discussion to post the reply to.
     *
     * @var int
     */
    public $discussionId;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to assign to the new post.
     *
     * @var array
     */
    public $data;

    /**
     * The IP address of the actor.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * @var bool
     */
    public $isFirstPost;

    /**
     * @param int $discussionId The ID of the discussion to post the reply to.
     * @param User $actor The user who is performing the action.
     * @param array $data The attributes to assign to the new post.
     * @param string $ipAddress The IP address of the actor.
     */
    public function __construct($discussionId, User $actor, array $data, $ipAddress = null, bool $isFirstPost = false)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
        $this->isFirstPost = $isFirstPost;
    }
}
