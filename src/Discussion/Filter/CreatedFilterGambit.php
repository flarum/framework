<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Illuminate\Database\Query\Builder;

class CreatedFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'created:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $matches[3], $negate);
    }

    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        preg_match('/^'.$this->pattern.'$/i', 'created:'.$filterValue, $matches);

        $this->constrain($wrappedFilter->getQuery(), $matches[1], $matches[3], $negate);
    }

    public function constrain(Builder $query, $firstDate, $secondDate, $negate)
    {
        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($secondDate)) {
            $query->whereDate('created_at', $negate ? '!=' : '=', $firstDate);
        } else {
            $query->whereBetween('created_at', [$firstDate, $secondDate], 'and', $negate);
        }
    }
}
