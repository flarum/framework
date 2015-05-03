<?php namespace Flarum\Core\Commands;

class EditPostCommand
{
    public $postId;

    public $user;

    public $data;

    public function __construct($postId, $user, $data)
    {
        $this->postId = $postId;
        $this->user = $user;
        $this->data = $data;
    }
}
