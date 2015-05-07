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
    protected $pattern = 'sticky:(true|false)';

    /**
     * Apply conditions to the searcher, given matches from the gambit's
     * regex.
     *
     * @param array $matches The matches from the gambit's regex.
     * @param \Flarum\Core\Search\SearcherInterface $searcher
     * @return void
     */
    public function conditions($matches, SearcherInterface $searcher)
    {
        $sticky = $matches[1] === 'true';

        $searcher->query()->where('is_sticky', $sticky);
    }
}
