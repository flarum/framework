<?php namespace Flarum\Core\Commands;

class ReadDiscussionCommand
{
    public $discussionId;

    public $user;

    public $readNumber;

    public function __construct($discussionId, $user, $readNumber)
    {
        $this->discussionId = $discussionId;
        $this->user = $user;
        $this->readNumber = $readNumber;
    }
}
