<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Discussion;

class DiscussionWasStarted
{
    public $discussion;

    public function __construct(Discussion $discussion)
    {
        $this->discussion = $discussion;
    }
}
