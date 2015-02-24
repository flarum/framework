<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Post;

class PostWasDeleted
{
    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}
