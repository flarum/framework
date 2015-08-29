<?php namespace Flarum\Subscriptions\Listeners;

use Flarum\Events\RegisterDiscussionGambits;
use Flarum\Events\DiscussionSearchWillBePerformed;

class HideIgnoredDiscussions
{
    public function subscribe($events)
    {
        $events->listen(RegisterDiscussionGambits::class, [$this, 'addGambit']);
        $events->listen(DiscussionSearchWillBePerformed::class, [$this, 'filterIgnored']);
    }

    public function addGambit(RegisterDiscussionGambits $event)
    {
        $event->gambits->add('Flarum\Subscriptions\Gambits\SubscriptionGambit');
    }

    public function filterIgnored(DiscussionSearchWillBePerformed $event)
    {
        if (! $event->criteria->query) {
            // might be better as `id IN (subquery)`?
            $actor = $event->search->getActor();
            $event->search->getQuery()->whereNotExists(function ($query) use ($actor) {
                $query->selectRaw(1)
                      ->from('users_discussions')
                      ->whereRaw('discussion_id = discussions.id')
                      ->where('user_id', $actor->id)
                      ->where('subscription', 'ignore');
            });
        }
    }
}
