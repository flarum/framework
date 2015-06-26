<?php namespace Flarum\Sticky;

use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class StickyGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     *
     * @var string
     */
    protected $pattern = 'is:sticky';

    /**
     * Apply conditions to the searcher, given matches from the gambit's
     * regex.
     *
     * @param array $matches The matches from the gambit's regex.
     * @param \Flarum\Core\Search\SearcherInterface $searcher
     * @return void
     */
    protected function conditions(SearcherInterface $searcher, array $matches, $negate)
    {
        $searcher->getQuery()->where('is_sticky', ! $negate);
    }
}
