<?php namespace Flarum\Core\Commands;

class PostReplyCommand
{
    public $discussionId;

    public $content;

    public $user;

    public function __construct($discussionId, $content, $user)
    {
        $this->discussionId = $discussionId;
        $this->content = $content;
        $this->user = $user;
    }
}
