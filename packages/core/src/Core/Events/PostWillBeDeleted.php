<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Post;

class PostWillBeDeleted
{
    public $post;

    public $command;

    public function __construct(Post $post, $command)
    {
        $this->post = $post;
        $this->command = $command;
    }
}
