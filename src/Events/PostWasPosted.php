<?php namespace Flarum\Events;

use Flarum\Core\Posts\Post;

class PostWasPosted
{
    /**
     * The post that was posted.
     *
     * @var Post
     */
    public $post;

    /**
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}
