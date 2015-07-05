<?php namespace Flarum\Core\Search;

interface Gambit
{
    /**
     * Apply conditions to the searcher for a bit of the search string.
     *
     * @param Search $search
     * @param string $bit The piece of the search string.
     * @return bool Whether or not the gambit was active for this bit.
     */
    public function apply(Search $search, $bit);
}
