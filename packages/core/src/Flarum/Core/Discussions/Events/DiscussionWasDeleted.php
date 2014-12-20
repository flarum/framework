<?php namespace Flarum\Core\Discussions\Events;

use Flarum\Core\Discussions\Discussion;

class DiscussionWasDeleted
{
    public $discussion;

    public function __construct(Discussion $discussion)
    {
        $this->discussion = $discussion;
    }
}
