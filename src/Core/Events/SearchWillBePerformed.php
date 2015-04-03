<?php namespace Flarum\Core\Events;

use Flarum\Core\Search\SearcherInterface;

class SearchWillBePerformed
{
    public $searcher;

    public $criteria;

    public function __construct(SearcherInterface $searcher, $criteria)
    {
        $this->searcher = $searcher;
        $this->criteria = $criteria;
    }
}
