<?php namespace Flarum\Core\Commands;

class EditDiscussionCommand
{
    public $discussionId;

    public $user;

    public $data;

    public function __construct($discussionId, $user, $data)
    {
        $this->discussionId = $discussionId;
        $this->user = $user;
        $this->data = $data;
    }
}
