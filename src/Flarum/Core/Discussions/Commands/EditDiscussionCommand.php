<?php namespace Flarum\Core\Discussions\Commands;

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
