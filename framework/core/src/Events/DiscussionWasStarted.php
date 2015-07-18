<?php namespace Flarum\Events;

use Flarum\Core\Discussions\Discussion;

class DiscussionWasStarted
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @param Discussion $discussion
     */
    public function __construct(Discussion $discussion)
    {
        $this->discussion = $discussion;
    }
}
