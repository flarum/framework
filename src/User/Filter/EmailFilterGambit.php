<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Illuminate\Database\Query\Builder;

class EmailFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'email:(.+)';

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search->getActor()->hasPermission('user.edit')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'email';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        if (! $wrappedFilter->getActor()->hasPermission('user.edit')) {
            return;
        }

        $this->constrain($wrappedFilter->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawEmail, bool $negate)
    {
        $email = trim($rawEmail, '"');

        $query->where('email', $negate ? '!=' : '=', $email);
    }
}
