<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\DiscussionState;

class DiscussionStateWillBeSaved
{
    public $state;

    public $command;

    public function __construct(DiscussionState $state, $command)
    {
        $this->state = $state;
        $this->command = $command;
    }
}
