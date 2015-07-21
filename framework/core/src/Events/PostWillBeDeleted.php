<?php namespace Flarum\Events;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;

class PostWillBeDeleted
{
    /**
     * The post that is going to be deleted.
     *
     * @var Post
     */
    public $post;

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
     * @param Post $post
     * @param User $actor
     * @param array $data
     */
    public function __construct(Post $post, User $actor, array $data)
    {
        $this->post = $post;
        $this->actor = $actor;
        $this->data = $data;
    }
}
