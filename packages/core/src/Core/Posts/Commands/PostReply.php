<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Users\User;

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
     * @param int $discussionId The ID of the discussion to post the reply to.
     * @param User $actor The user who is performing the action.
     * @param array $data The attributes to assign to the new post.
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
