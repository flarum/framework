<?php namespace Flarum\Core\Commands;

class StartDiscussionCommand
{
    public $user;

    public $forum;

    public $data;

    public function __construct($user, $forum, $data)
    {
        $this->user = $user;
        $this->forum = $forum;
        $this->data = $data;
    }
}
