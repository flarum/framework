<?php namespace Flarum\Core\Posts\Events;

use Flarum\Core\Posts\Post;

class PostWasRestored
{
    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}
