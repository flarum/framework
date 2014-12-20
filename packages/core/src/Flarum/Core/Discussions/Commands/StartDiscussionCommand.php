<?php namespace Flarum\Core\Discussions\Commands;

class StartDiscussionCommand
{
    public $title;

    public $content;

    public $user;

    public function __construct($title, $content, $user)
    {
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
    }
}
