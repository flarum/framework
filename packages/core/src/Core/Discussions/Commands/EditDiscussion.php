<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Users\User;

class EditDiscussion
{
    /**
     * The ID of the discussion to edit.
     *
     * @var integer
     */
    public $discussionId;

    /**
     * The user performing the action.
     *
     * @var \Flarum\Core\Users\User
     */
    public $actor;

    /**
     * The attributes to update on the discussion.
     *
     * @var array
     */
    public $data;

    /**
     * @param integer $discussionId The ID of the discussion to edit.
     * @param \Flarum\Core\Users\User $actor The user performing the action.
     * @param array $data The attributes to update on the discussion.
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
