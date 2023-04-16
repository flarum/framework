<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Query;

use Carbon\Carbon;
use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\User\Guest;
use Flarum\User\UserRepository;
use Illuminate\Database\Query\Builder;

class SuspendedFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

    /**
     * @param \Flarum\User\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    protected function getGambitPattern()
    {
        return 'is:suspended';
    }

    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        if (! $search->getActor()->can('suspend', new Guest())) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'suspended';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        if (! $filterState->getActor()->can('suspend', new Guest())) {
            return false;
        }

        $this->constrain($filterState->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate)
    {
        $query->where(function ($query) use ($negate) {
            if ($negate) {
                $query->where('suspended_until', null)->orWhere('suspended_until', '<', Carbon::now());
            } else {
                $query->where('suspended_until', '>', Carbon::now());
            }
        });
    }
}
