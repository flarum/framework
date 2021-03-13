<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class SubscriptionFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    protected function getGambitPattern()
    {
        return 'is:(follow|ignor)(?:ing|ed)';
    }

    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'subscription';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        preg_match('/^'.$this->getGambitPattern().'$/i', 'is:'.$filterValue, $matches);

        $this->constrain($filterState->getQuery(), $filterState->getActor(), $matches[1], $negate);
    }

    protected function constrain(Builder $query, User $actor, string $subscriptionType, bool $negate)
    {
        $method = $negate ? 'whereNotIn' : 'whereIn';
        $query->$method('id', function ($query) use ($actor, $subscriptionType) {
            $query->select('discussion_id')
            ->from('discussion_user')
            ->where('user_id', $actor->id)
                ->where('subscription', $subscriptionType === 'follow' ? 'follow' : 'ignore');
        });
    }
}
