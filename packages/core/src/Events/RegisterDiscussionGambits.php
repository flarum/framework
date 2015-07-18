<?php namespace Flarum\Events;

use Flarum\Core\Search\GambitManager;

class RegisterDiscussionGambits
{
    /**
     * @var GambitManager
     */
    public $gambits;

    /**
     * @param GambitManager $gambits
     */
    public function __construct(GambitManager $gambits)
    {
        $this->gambits = $gambits;
    }
}
