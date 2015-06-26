<?php namespace Flarum\Subscriptions;

use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class SubscriptionGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     *
     * @var string
     */
    protected $pattern = 'is:(follow|ignor)(?:ing|ed)';

    /**
     * Apply conditions to the searcher, given matches from the gambit's
     * regex.
     *
     * @param array $matches The matches from the gambit's regex.
     * @param \Flarum\Core\Search\SearcherInterface $searcher
     * @return void
     */
    protected function conditions(SearcherInterface $searcher, array $matches, $negate)
    {
        $user = $searcher->getUser();

        // might be better as `id IN (subquery)`?
        $method = $negate ? 'whereNotExists' : 'whereExists';
        $searcher->getQuery()->$method(function ($query) use ($user, $matches) {
            $query->select(app('db')->raw(1))
                  ->from('users_discussions')
                  ->whereRaw('discussion_id = discussions.id')
                  ->where('user_id', $user->id)
                  ->where('subscription', $matches[1] === 'follow' ? 'follow' : 'ignore');
        });
    }
}
