<?php namespace Flarum\Core\Events;

use Flarum\Core\Search\GambitManager;

class RegisterDiscussionGambits
{
    public $gambits;

    public function __construct(GambitManager $gambits)
    {
        $this->gambits = $gambits;
    }
}
