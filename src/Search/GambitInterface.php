<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

interface GambitInterface
{
    /**
     * Apply conditions to the searcher for a bit of the search string.
     *
     * @param SearchState $search
     * @param string $bit The piece of the search string.
     * @return bool Whether or not the gambit was active for this bit.
     */
    public function apply(SearchState $search, $bit);
}
