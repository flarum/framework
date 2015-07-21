<?php namespace Flarum\Events;

use Flarum\Core\Search\GambitManager;

class RegisterUserGambits
{
    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @param GambitManager $gambits
     */
    public function __construct(GambitManager $gambits)
    {
        $this->gambits = $gambits;
    }

    /**
     * @param string $gambit
     */
    public function register($gambit)
    {
        $this->gambits->add($gambit);
    }
}
