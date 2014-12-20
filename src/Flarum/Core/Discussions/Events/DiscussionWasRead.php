<?php namespace Flarum\Core\Discussions\Events;

use Flarum\Core\Discussions\DiscussionState;

class DiscussionWasRead
{
    public $state;

    public function __construct(DiscussionState $state)
    {
        $this->state = $state;
    }
}
