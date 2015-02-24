<?php namespace Flarum\Core\Commands;

class StartDiscussionCommand
{
    public $title;

    public $content;

    public $user;

    public $forum;

    public function __construct($title, $content, $user, $forum)
    {
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
        $this->forum = $forum;
    }
}
