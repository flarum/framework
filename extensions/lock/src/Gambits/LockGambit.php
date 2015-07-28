<?php namespace Flarum\Lock\Gambits;

use Flarum\Core\Search\Search;
use Flarum\Core\Search\RegexGambit;

class LockGambit extends RegexGambit
{
    protected $pattern = 'is:locked';

    protected function conditions(Search $search, array $matches, $negate)
    {
        $search->getQuery()->where('is_locked', ! $negate);
    }
}
