<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Query\AbstractQueryState;

class SearchState extends AbstractQueryState
{
    /**
     * @var GambitInterface[]
     */
    protected $activeGambits = [];

    /**
     * Get a list of the gambits that are active in this search.
     *
     * @return GambitInterface[]
     */
    public function getActiveGambits()
    {
        return $this->activeGambits;
    }

    /**
     * Add a gambit as being active in this search.
     *
     * @param GambitInterface $gambit
     * @return void
     */
    public function addActiveGambit(GambitInterface $gambit)
    {
        $this->activeGambits[] = $gambit;
    }
}
