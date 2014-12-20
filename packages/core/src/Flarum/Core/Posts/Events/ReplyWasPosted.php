<?php namespace Flarum\Core\Posts\Events;

use Flarum\Core\Posts\Post;

class ReplyWasPosted
{
    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}
