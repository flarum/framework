<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class CreatedFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $value = $this->asString($value);

        preg_match('/^(\d{4}-\d{2}-\d{2})(?:\.\.(\d{4}-\d{2}-\d{2}))?$/', $value, $matches);

        $from = Arr::get($matches, 1);
        $to = Arr::get($matches, 2);

        $this->constrain($state->getQuery(), $from, $to, $negate);
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
