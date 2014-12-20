<?php namespace Flarum\Core\Discussions\Commands;

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
