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
    protected array $activeGambits = [];

    /**
     * Get a list of the gambits that are active in this search.
     *
     * @return GambitInterface[]
     */
    public function getActiveGambits(): array
    {
        return $this->activeGambits;
    }

    /**
     * Add a gambit as being active in this search.
     */
    public function addActiveGambit(GambitInterface $gambit): void
    {
        $this->activeGambits[] = $gambit;
    }
}
