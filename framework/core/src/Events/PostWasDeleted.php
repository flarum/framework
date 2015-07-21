<?php namespace Flarum\Events;

use Flarum\Core\Posts\Post;

class PostWasDeleted
{
    /**
     * The post that was deleted.
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
