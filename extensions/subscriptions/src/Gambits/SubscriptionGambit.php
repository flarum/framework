<?php namespace Flarum\Subscriptions\Gambits;

use Flarum\Core\Search\Search;
use Flarum\Core\Search\RegexGambit;

class SubscriptionGambit extends RegexGambit
{
    protected $pattern = 'is:(follow|ignor)(?:ing|ed)';

    protected function conditions(Search $search, array $matches, $negate)
    {
        $actor = $search->getActor();

        // might be better as `id IN (subquery)`?
        $method = $negate ? 'whereNotExists' : 'whereExists';
        $search->getQuery()->$method(function ($query) use ($actor, $matches) {
            $query->select(app('db')->raw(1))
                  ->from('users_discussions')
                  ->whereRaw('discussion_id = discussions.id')
                  ->where('user_id', $actor->id)
                  ->where('subscription', $matches[1] === 'follow' ? 'follow' : 'ignore');
        });
    }
}
