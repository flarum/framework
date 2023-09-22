<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class SubscriptionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'subscription';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $filterValue = $this->asString($filterValue);

        preg_match('/^(follow|ignor)(?:ing|ed)$/i', $filterValue, $matches);

        $this->constrain($filterState->getQuery(), $filterState->getActor(), $matches[1], $negate);
    }

    protected function constrain(Builder $query, User $actor, string $subscriptionType, bool $negate): void
    {
        $method = $negate ? 'whereNotIn' : 'whereIn';
        $query->$method('discussions.id', function ($query) use ($actor, $subscriptionType) {
            $query->select('discussion_id')
            ->from('discussion_user')
            ->where('user_id', $actor->id)
                ->where('subscription', $subscriptionType === 'follow' ? 'follow' : 'ignore');
        });
    }
}
