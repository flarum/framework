<?php namespace Flarum\Events;

use Flarum\Core\Posts\CommentPost;

class PostWasRestored
{
    /**
     * The post that was restored.
     *
     * @var CommentPost
     */
    public $post;

    /**
     * @param CommentPost $post The post that was restored.
     */
    public function __construct(CommentPost $post)
    {
        $this->post = $post;
    }
}
