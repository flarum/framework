<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class LockedFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    protected function getGambitPattern(): string
    {
        return 'is:locked';
    }

    protected function conditions(SearchState $search, array $matches, bool $negate): void
    {
        $this->constrain($search->getQuery(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'locked';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate): void
    {
        $query->where('is_locked', ! $negate);
    }
}
