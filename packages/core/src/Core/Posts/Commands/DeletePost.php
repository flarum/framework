<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Users\User;

class DeletePost
{
    /**
     * The ID of the post to delete.
     *
     * @var int
     */
    public $postId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * Any other user input associated with the action. This is unused by
     * default, but may be used by extensions.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $postId The ID of the post to delete.
     * @param User $actor The user performing the action.
     * @param array $data Any other user input associated with the action. This
     *     is unused by default, but may be used by extensions.
     */
    public function __construct($postId, User $actor, array $data = [])
    {
        $this->postId = $postId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
