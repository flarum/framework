<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class SubscriptionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'subscription';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $value = $this->asString($value);

        preg_match('/^(follow|ignor)(?:ing|ed)$/i', $value, $matches);

        $this->constrain($state->getQuery(), $state->getActor(), $matches[1], $negate);
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
