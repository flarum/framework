<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Discussion;

class DiscussionWillBeSaved
{
    public $discussion;

    public $command;

    public function __construct(Discussion $discussion, $command)
    {
        $this->discussion = $discussion;
        $this->command = $command;
    }
}
