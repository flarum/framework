<?php namespace Flarum\Core\Posts\Events;

use Flarum\Core\Posts\CommentPost;

class PostWasRevised
{
    /**
     * The post that was revised.
     *
     * @var CommentPost
     */
    public $post;

    /**
     * @param CommentPost $post The post that was revised.
     */
    public function __construct(CommentPost $post)
    {
        $this->post = $post;
    }
}
