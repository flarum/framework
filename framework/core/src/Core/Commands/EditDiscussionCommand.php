<?php namespace Flarum\Core\Commands;

class EditDiscussionCommand
{
    public $discussionId;

    public $user;

    public $title;

    public function __construct($discussionId, $user)
    {
        $this->discussionId = $discussionId;
        $this->user = $user;
    }
}
