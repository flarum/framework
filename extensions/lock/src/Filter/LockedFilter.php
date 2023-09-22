<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Filter;

use Flarum\Search\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class LockedFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'locked';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate): void
    {
        $query->where('is_locked', ! $negate);
    }
}
