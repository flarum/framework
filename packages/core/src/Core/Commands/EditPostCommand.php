<?php namespace Flarum\Core\Commands;

class EditPostCommand
{
    public $postId;

    public $user;

    public $content;

    public $isHidden;

    public function __construct($postId, $user)
    {
        $this->postId = $postId;
        $this->user = $user;
    }
}
