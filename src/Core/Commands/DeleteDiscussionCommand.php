<?php namespace Flarum\Core\Commands;

class DeleteDiscussionCommand
{
    public $discussionId;

    public $user;

    public function __construct($discussionId, $user)
    {
        $this->discussionId = $discussionId;
        $this->user = $user;
    }
}
