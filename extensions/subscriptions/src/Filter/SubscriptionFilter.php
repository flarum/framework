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

    /**
     * The values accepted for each subscription stored in `discussion_user`.
     *
     * @var array<string, string[]>
     */
    protected const VALUES = [
        'follow' => ['follow', 'following', 'followed'],
        'ignore' => ['ignore', 'ignoring', 'ignored'],
    ];

    public function getFilterKey(): string
    {
        return 'subscription';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $value = $this->asString($value);

        $canonicalValue = $this->resolveCanonicalValue($value);

        if ($canonicalValue === null) {
            // Unrecognised value — match nothing rather than everything.
            $state->getQuery()->whereRaw('0 = 1');

            return;
        }

        $this->constrain($state->getQuery(), $state->getActor(), $canonicalValue, $negate);
    }

    /**
     * Resolve a filter value to the subscription stored in the database,
     * ignoring case as the previous pattern did. Returns null if unrecognised.
     */
    protected function resolveCanonicalValue(string $value): ?string
    {
        $value = mb_strtolower($value);

        foreach (static::VALUES as $subscription => $values) {
            if (in_array($value, $values, true)) {
                return $subscription;
            }
        }

        return null;
    }

    protected function constrain(Builder $query, User $actor, string $subscriptionType, bool $negate): void
    {
        $method = $negate ? 'whereNotIn' : 'whereIn';
        $query->$method('discussions.id', function ($query) use ($actor, $subscriptionType) {
            $query->select('discussion_id')
                ->from('discussion_user')
                ->where('user_id', $actor->id)
                ->where('subscription', $subscriptionType);
        });
    }
}
