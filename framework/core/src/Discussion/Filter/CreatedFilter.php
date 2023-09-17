<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class CreatedFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $filterValue = is_string($filterValue)
            ? $this->asString($filterValue)
            : $this->asStringArray($filterValue);

        if (is_array($filterValue)) {
            $from = Arr::get($filterValue, 'from');
            $to = Arr::get($filterValue, 'to');
        } else {
            $from = $filterValue;
            $to = null;
        }

        $this->constrain($filterState->getQuery(), $from, $to, $negate);
    }

    public function constrain(Builder $query, ?string $from, ?string $to, bool $negate): void
    {
        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($to)) {
            $query->whereDate('created_at', $negate ? '!=' : '=', $from);
        } else {
            $query->whereBetween('created_at', [$from, $to], 'and', $negate);
        }
    }
}
