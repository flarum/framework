<?php namespace Flarum\Core\Commands;

class DeletePostCommand
{
    public $postId;

    public $user;

    public function __construct($postId, $user)
    {
        $this->postId = $postId;
        $this->user = $user;
    }
}
