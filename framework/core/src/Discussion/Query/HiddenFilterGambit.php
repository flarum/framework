<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class HiddenFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    public function getGambitPattern(): string
    {
        return 'is:hidden';
    }

    protected function conditions(SearchState $search, array $matches, bool $negate): void
    {
        $this->constrain($search->getQuery(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate): void
    {
        $query->where(function ($query) use ($negate) {
            if ($negate) {
                $query->whereNull('hidden_at')->where('comment_count', '>', 0);
            } else {
                $query->whereNotNull('hidden_at')->orWhere('comment_count', 0);
            }
        });
    }
}
