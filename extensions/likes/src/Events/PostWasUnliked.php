<?php namespace Flarum\Likes\Events;

use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;

class PostWasUnliked
{
    /**
     * @var \Flarum\Core\Models\Post
     */
    public $post;

    /**
     * @var \Flarum\Core\Models\User
     */
    public $user;

    /**
     * @param \Flarum\Core\Models\Post $post
     * @param \Flarum\Core\Models\User $user
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }
}
