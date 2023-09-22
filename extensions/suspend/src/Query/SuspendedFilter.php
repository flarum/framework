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
use Flarum\User\Guest;
use Flarum\User\UserRepository;
use Illuminate\Database\Query\Builder;

class SuspendedFilter implements FilterInterface
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'suspended';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        if (! $filterState->getActor()->can('suspend', new Guest())) {
            return;
        }

        $this->constrain($filterState->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate): void
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
