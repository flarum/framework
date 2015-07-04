<?php namespace Flarum\Core\Discussions\Events;

use Flarum\Core\Discussions\DiscussionState;

class DiscussionStateWillBeSaved
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
