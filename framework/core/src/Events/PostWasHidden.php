<?php namespace Flarum\Events;

use Flarum\Core\Posts\CommentPost;

class PostWasHidden
{
    /**
     * The post that was hidden.
     *
     * @var CommentPost
     */
    public $post;

    /**
     * @param CommentPost $post
     */
    public function __construct(CommentPost $post)
    {
        $this->post = $post;
    }
}
