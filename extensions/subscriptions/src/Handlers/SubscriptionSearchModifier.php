<?php namespace Flarum\Subscriptions\Handlers;

use Flarum\Core\Events\DiscussionSearchWillBePerformed;

class SubscriptionSearchModifier
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionSearchWillBePerformed', __CLASS__.'@filterIgnored');
    }

    public function filterIgnored(DiscussionSearchWillBePerformed $event)
    {
        if (! $event->criteria->query) {
            // might be better as `id IN (subquery)`?
            $user = $event->criteria->user;
            $event->searcher->getQuery()->whereNotExists(function ($query) use ($user) {
                $query->select(app('db')->raw(1))
                      ->from('users_discussions')
                      ->whereRaw('discussion_id = discussions.id')
                      ->where('user_id', $user->id)
                      ->where('subscription', 'ignore');
            });
        }
    }
}
