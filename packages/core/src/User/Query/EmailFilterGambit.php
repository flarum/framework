<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class EmailFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        if (! $search->getActor()->hasPermission('user.edit')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'email:(.+)';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'email';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        if (! $filterState->getActor()->hasPermission('user.edit')) {
            return;
        }

        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawEmail, bool $negate)
    {
        $email = trim($rawEmail, '"');

        $query->where('email', $negate ? '!=' : '=', $email);
    }
}
