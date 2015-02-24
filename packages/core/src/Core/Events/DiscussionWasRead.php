<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\DiscussionState;

class DiscussionWasRead
{
    public $state;

    public function __construct(DiscussionState $state)
    {
        $this->state = $state;
    }
}
