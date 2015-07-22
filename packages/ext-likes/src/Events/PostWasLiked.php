<?php namespace Flarum\Likes\Events;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;

class PostWasLiked
{
    /**
     * @var Post
     */
    public $post;

    /**
     * @var User
     */
    public $user;

    /**
     * @param Post $post
     * @param User $user
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }
}
