<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Discussion;

class DiscussionWasDeleted
{
    public $discussion;

    public function __construct(Discussion $discussion)
    {
        $this->discussion = $discussion;
    }
}
