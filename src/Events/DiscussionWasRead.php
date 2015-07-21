<?php namespace Flarum\Events;

use Flarum\Core\Discussions\DiscussionState;

class DiscussionWasRead
{
    /**
     * @var DiscussionState
     */
    public $state;

    /**
     * @param DiscussionState $state
     */
    public function __construct(DiscussionState $state)
    {
        $this->state = $state;
    }
}
